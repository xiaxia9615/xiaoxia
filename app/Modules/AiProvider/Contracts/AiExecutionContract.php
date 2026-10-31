<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Contracts;

/**
 * AiProvider 模块对外执行契约
 *
 * 封装 AI 调用的完整编排：选择最低成本 Provider（智能路由）→
 * 按其 driver 配置实例化客户端 → 发起调用 → 熔断记录（连续 3 次失败踢出路由池 60 秒）。
 *
 * 调用失败时异常原样向上抛出（保持调用方原有错误处理不变）。
 */
interface AiExecutionContract
{
    /**
     * 执行一次 AI 调用（智能路由 + 熔断保护）。
     *
     * @return array{provider: array{id: int, name: string, display_name: string}, content: string, input_tokens: int, output_tokens: int, cost: float}
     */
    public function execute(string $prompt): array;
}
