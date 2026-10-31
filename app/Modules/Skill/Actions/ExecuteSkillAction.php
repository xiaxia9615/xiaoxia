<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Modules\AiProvider\Actions\SelectBestProviderAction;
use App\Modules\AiProvider\Services\AiClientFactory;
use App\Modules\AiProvider\Services\CircuitBreaker;
use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use Illuminate\Support\Facades\Event;
use Throwable;

final class ExecuteSkillAction
{
    public function __construct(
        private readonly SelectBestProviderAction $selectBestProvider,
        private readonly AiClientFactory $aiClientFactory,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    /**
     * 智能路由网关：选择最低成本 Provider，通过工厂按其配置（driver）实例化客户端，
     * 发起 AI 调用并记录用量计费。
     * 调用失败时记录熔断（连续 3 次失败后该 Provider 被踢出路由池 60 秒），
     * 异常继续向上抛出，保持原有 API 错误处理不变。
     *
     * @return array<string, mixed>
     */
    public function execute(int $userId, string $prompt): array
    {
        $provider = $this->selectBestProvider->execute();
        $providerId = (int) $provider['id'];

        // 数据驱动：按选中 Provider 的 driver 配置实例化对应客户端
        $aiClient = $this->aiClientFactory->make($provider);

        try {
            $response = $aiClient->chat($prompt);
        } catch (Throwable $e) {
            // 记录失败：达到阈值后触发熔断，路由池自动剔除该 Provider
            $this->circuitBreaker->recordFailure($providerId);
            // 保持原有 API 错误处理：异常继续向上抛出
            throw $e;
        }

        // 调用成功：清空失败计数
        $this->circuitBreaker->recordSuccess($providerId);

        Event::dispatch(new AiUsageRecorded(
            userId: $userId,
            providerId: $providerId,
            cost: $response->cost,
            inputTokens: $response->inputTokens,
            outputTokens: $response->outputTokens,
        ));

        return [
            'provider' => [
                'id' => $providerId,
                'name' => (string) $provider['name'],
                'display_name' => (string) $provider['display_name'],
            ],
            'content' => $response->content,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'cost' => $response->cost,
        ];
    }
}
