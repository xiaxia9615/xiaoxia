<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Support\Facades\DB;

/**
 * AI 用量扣费流水服务：封装 ai_usage_deductions 表写入。
 *
 * 该表迁移归属 Ledger 模块；Wallet 模块监听 AiUsageRecorded 事件时
 * 通过本服务写入流水，避免跨模块裸表访问。
 */
final class AiUsageDeductionService
{
    /**
     * 写入一条 AI 用量扣费流水（须与扣款在同一事务内调用）。
     */
    public function record(
        int $userId,
        int $providerId,
        float $cost,
        int $inputTokens,
        int $outputTokens,
    ): void {
        DB::table('ai_usage_deductions')->insert([
            'user_id' => $userId,
            'provider_id' => $providerId,
            'cost' => $cost,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
