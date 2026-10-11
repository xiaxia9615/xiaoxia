<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Support\Str;
use RuntimeException;

final class SkillCallService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function call(User $caller, int $productId, array $input): array
    {
        $product = Product::query()->where('type', 1)->find($productId);
        if (! $product) throw new RuntimeException("Skill not found: {$productId}");

        $detail = SkillDetail::query()->where('product_id', $product->id)->first();
        if (! $detail) throw new RuntimeException("Skill detail not found: {$productId}");

        $apiKey = ApiKey::query()->where('user_id', $caller->id)->first();
        if (! $apiKey) throw new RuntimeException('no api key for this user');

        $cost = (int) $product->price;

        $provider   = (int) round($cost * $detail->provider_share / 100);
        $platform   = (int) round($cost * $detail->platform_share / 100);
        $influencer = (int) round($cost * $detail->influencer_share / 100);
        $donation   = $cost - $provider - $platform - $influencer;

        $output = [
            'skill'    => $product->name,
            'endpoint' => $detail->endpoint,
            'method'   => $detail->method,
            'input'    => $input,
            'message'  => '模拟调用成功',
        ];

        $call = ApiCall::create([
            'request_id'        => (string) Str::uuid(),
            'user_id'           => $caller->id,
            'api_key_id'        => $apiKey->id,
            'product_id'        => $product->id,
            'provider_id'       => $product->user_id,
            'input'             => $input,
            'output'            => $output,
            'status_code'       => 200,
            'duration_ms'       => 42,
            'cost'              => $cost,
            'provider_income'   => $provider,
            'platform_income'   => $platform,
            'influencer_income' => $influencer,
            'donation_amount'   => $donation,
        ]);

        $this->ledger->record('api_call', $call->id, [
            ['owner_type' => 'user',     'owner_id' => $caller->id,       'account_type' => 'provider',   'direction' => 'credit', 'amount' => $provider],
            ['owner_type' => 'platform', 'owner_id' => 0,                 'account_type' => 'platform',   'direction' => 'credit', 'amount' => $platform],
            ['owner_type' => 'platform', 'owner_id' => 0,                 'account_type' => 'influencer', 'direction' => 'credit', 'amount' => $influencer],
            ['owner_type' => 'platform', 'owner_id' => 0,                 'account_type' => 'donation',   'direction' => 'credit', 'amount' => $donation],
            ['owner_type' => 'user',     'owner_id' => $caller->id,       'account_type' => 'balance',    'direction' => 'debit',  'amount' => $cost],
        ]);

        return [
            'call_id' => $call->id,
            'result'  => $output,
            'billing' => [
                'cost' => $cost, 'provider' => $provider,
                'platform' => $platform, 'influencer' => $influencer,
                'donation' => $donation,
            ],
        ];
    }
}
