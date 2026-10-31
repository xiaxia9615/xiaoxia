<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Modules\AiProvider\Contracts\AiExecutionContract;
use App\Modules\Ledger\Events\AiUsageRecorded;
use Illuminate\Support\Facades\Event;

final class ExecuteSkillAction
{
    public function __construct(
        private readonly AiExecutionContract $aiExecution,
    ) {}

    /**
     * 智能路由网关：通过 AiExecutionContract 委托 AiProvider 模块完成
     * AI 调用（选择最低成本 Provider → 按 driver 实例化客户端 → 调用 + 熔断保护），
     * 并记录用量计费。
     *
     * 调用失败时异常由 AiExecutionContract 原样向上抛出，保持原有 API 错误处理不变。
     *
     * @return array<string, mixed>
     */
    public function execute(int $userId, string $prompt): array
    {
        $result = $this->aiExecution->execute($prompt);

        Event::dispatch(new AiUsageRecorded(
            userId: $userId,
            providerId: $result['provider']['id'],
            cost: $result['cost'],
            inputTokens: $result['input_tokens'],
            outputTokens: $result['output_tokens'],
        ));

        return $result;
    }
}
