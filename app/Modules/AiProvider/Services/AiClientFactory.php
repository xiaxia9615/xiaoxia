<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Services;

use App\Modules\AiProvider\Clients\GlmClient;
use App\Modules\AiProvider\Clients\OpenAiCompatibleClient;
use App\Modules\AiProvider\Contracts\AiClientInterface;

/**
 * AI 客户端工厂：根据 Provider 配置（driver 字段）数据驱动地实例化客户端。
 *
 * driver = openai_compatible → OpenAiCompatibleClient（注入 base_url / model_name / 成本字段）
 * driver 为空或其他 → 默认 GlmClient（保持既有行为兼容）
 *
 * 接入新的 OpenAI 兼容供应商只需填充数据（Seeder），严禁新建重复的 Client 类。
 */
final class AiClientFactory
{
    public const DRIVER_OPENAI_COMPATIBLE = 'openai_compatible';

    /**
     * @param array<string, mixed> $provider Provider 信息数组（来自路由策略，严禁传 Model）
     */
    public function make(array $provider): AiClientInterface
    {
        $driver = (string) ($provider['driver'] ?? '');

        if ($driver === self::DRIVER_OPENAI_COMPATIBLE) {
            return new OpenAiCompatibleClient(
                baseUrl:        (string) ($provider['base_url'] ?? ''),
                modelName:      (string) ($provider['model_name'] ?? ''),
                costPer1kInput: (float) ($provider['cost_per_1k_input'] ?? 0),
                costPer1kOutput: (float) ($provider['cost_per_1k_output'] ?? 0),
                apiKey:         isset($provider['api_key']) ? (string) $provider['api_key'] : null,
            );
        }

        // 默认驱动：智谱 GLM 专用客户端（历史行为兼容）
        return new GlmClient();
    }
}
