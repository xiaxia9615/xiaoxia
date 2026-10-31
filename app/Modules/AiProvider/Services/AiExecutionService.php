<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Services;

use App\Modules\AiProvider\Actions\SelectBestProviderAction;
use App\Modules\AiProvider\Contracts\AiExecutionContract;
use Throwable;

/**
 * AI 执行服务：智能路由网关的模块内实现。
 *
 * 完整封装原本散落在 Skill 模块的编排逻辑：
 * 选择最低成本 Provider（SelectBestProviderAction）→
 * 按其 driver 配置实例化客户端（AiClientFactory）→
 * 发起 AI 调用并记录熔断（CircuitBreaker：连续 3 次失败踢出路由池 60 秒）。
 *
 * 调用失败时记录熔断后异常原样向上抛出，保持调用方原有错误处理不变。
 */
final class AiExecutionService implements AiExecutionContract
{
    public function __construct(
        private readonly SelectBestProviderAction $selectBestProvider,
        private readonly AiClientFactory $aiClientFactory,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    /**
     * 执行一次 AI 调用（智能路由 + 熔断保护）。
     *
     * @return array{provider: array{id: int, name: string, display_name: string}, content: string, input_tokens: int, output_tokens: int, cost: float}
     */
    public function execute(string $prompt): array
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
            // 保持原有错误处理：异常继续向上抛出
            throw $e;
        }

        // 调用成功：清空失败计数
        $this->circuitBreaker->recordSuccess($providerId);

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
