<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use App\Models\User;
use App\Modules\ApiKey\Contracts\ApiCallContract;
use App\Modules\ApiKey\Contracts\ApiKeyContract;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class SkillCallService
{
    public function __construct(
        private readonly LedgerContract $ledger,
        private readonly SkillProductContract $products,
        private readonly ApiKeyContract $apiKeys,
        private readonly ApiCallContract $apiCalls,
    ) {}

    public function call(User $caller, int $productId, array $input, ?string $requestId = null): array
    {
        if ($requestId) {
            $existing = $this->apiCalls->findByRequestId($requestId);
            if ($existing) {
                return [
                    'call_id' => $existing['id'],
                    'result' => $existing['output'],
                    'billing' => [
                        'cost' => $existing['cost'],
                        'provider' => $existing['provider_income'],
                        'platform' => $existing['platform_income'],
                        'influencer' => $existing['influencer_income'],
                        'donation' => $existing['donation_amount'],
                    ],
                ];
            }
        }
        $product = $this->products->findSkill($productId);
        if (! $product) {
            throw new RuntimeException("Skill not found: {$productId}");
        }
        $detail = SkillDetail::query()->where('product_id', $product['id'])->first();
        if (! $detail) {
            throw new RuntimeException("Skill detail not found: {$productId}");
        }
        $apiKey = $this->apiKeys->findActiveKeyForUser($caller->id);
        if (! $apiKey) {
            throw new RuntimeException('no api key for this user');
        }
        $cost = (int) $product['price'];
        $balance = $this->ledger->balance('user', $caller->id, 'balance');
        if ($balance < $cost) {
            throw new RuntimeException("余额不足：当前 {$balance} 分，需要 {$cost} 分");
        }
        $provider = intdiv($cost * $detail->provider_share, 100);
        $platform = intdiv($cost * $detail->platform_share, 100);
        $influencer = intdiv($cost * $detail->influencer_share, 100);
        $donation = intdiv($cost * $detail->donation_share, 100);
        $platform += $cost - $provider - $platform - $influencer - $donation;
        // 组装请求体：将用户 input 按 request_template 合并
        $template = $detail->request_template ?? [];
        $requestBody = array_merge($template, $input);
        // 发起真实 HTTP 调用
        $startTime = microtime(true);
        try {
            $http = Http::connectTimeout(5)->timeout(30);
            if ($detail->api_key) {
                $http = $http->withToken($detail->api_key);
            }
            $response = match (strtoupper($detail->method)) {
                'POST' => $http->post($detail->endpoint, $requestBody),
                'GET' => $http->get($detail->endpoint, $requestBody),
                'PUT' => $http->put($detail->endpoint, $requestBody),
                'PATCH' => $http->patch($detail->endpoint, $requestBody),
                'DELETE' => $http->delete($detail->endpoint, $requestBody),
                default => throw new RuntimeException("不支持的 HTTP 方法: {$detail->method}"),
            };
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            if ($response->failed()) {
                throw new RuntimeException(
                    "外部 API 返回错误：HTTP {$response->status()}"
                );
            }
            $output = $response->json() ?? ['raw' => $response->body()];
        } catch (ConnectionException $e) {
            throw new RuntimeException('外部 API 连接失败：'.$e->getMessage());
        } catch (RequestException $e) {
            throw new RuntimeException('外部 API 请求失败：'.$e->getMessage());
        }
        try {
            $callId = $this->apiCalls->create([
                'request_id' => $requestId ?: (string) Str::uuid(),
                'user_id' => $caller->id,
                'api_key_id' => $apiKey['id'],
                'product_id' => (int) $product['id'],
                'provider_id' => (int) $product['user_id'],
                'input' => $input,
                'output' => $output,
                'status_code' => $response->status(),
                'duration_ms' => $durationMs,
                'cost' => $cost,
                'provider_income' => $provider,
                'platform_income' => $platform,
                'influencer_income' => $influencer,
                'donation_amount' => $donation,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->apiCalls->findByRequestId($requestId);
            if ($existing) {
                return [
                    'call_id' => $existing['id'],
                    'result' => $existing['output'],
                    'billing' => [
                        'cost' => $existing['cost'],
                        'provider' => $existing['provider_income'],
                        'platform' => $existing['platform_income'],
                        'influencer' => $existing['influencer_income'],
                        'donation' => $existing['donation_amount'],
                    ],
                ];
            }
            throw $e;
        }
        $this->ledger->record('api_call', $callId, [
            ['owner_type' => 'user',     'owner_id' => $caller->id, 'account_type' => 'provider',   'direction' => 'credit', 'amount' => $provider],
            ['owner_type' => 'platform', 'owner_id' => 0,           'account_type' => 'platform',   'direction' => 'credit', 'amount' => $platform],
            ['owner_type' => 'platform', 'owner_id' => 0,           'account_type' => 'influencer', 'direction' => 'credit', 'amount' => $influencer],
            ['owner_type' => 'platform', 'owner_id' => 0,           'account_type' => 'donation',   'direction' => 'credit', 'amount' => $donation],
            ['owner_type' => 'user',     'owner_id' => $caller->id, 'account_type' => 'balance',    'direction' => 'debit',  'amount' => $cost],
        ]);

        return [
            'call_id' => $callId,
            'result' => $output,
            'billing' => [
                'cost' => $cost, 'provider' => $provider,
                'platform' => $platform, 'influencer' => $influencer,
                'donation' => $donation,
            ],
        ];
    }
}
