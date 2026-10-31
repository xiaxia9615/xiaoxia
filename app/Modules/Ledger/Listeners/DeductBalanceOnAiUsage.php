<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Listeners;

use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class DeductBalanceOnAiUsage
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    /**
     * AI 用量计费：真实扣减用户余额 + 写入扣费流水，二者在同一事务内原子完成。
     *
     * 精度换算说明：
     * - 账本记账单位为毫（1 元 = 1000 毫），ledger_entries.amount 为整数存储；
     * - 事件携带的 cost 为浮点元（如 0.003 元），换算为 3 毫后可被整数精确表示，无精度丢失；
     * - 换算使用字符串定点运算（sprintf 后去小数点），避免二进制浮点误差；
     * - 换算结果向上取整到毫（ceil），保证平台不因舍入少收，单次误差不超过 1 毫（0.001 元）。
     *
     * 并发安全：真实扣款由 LedgerService 的 debit 方法完成——事务内悲观锁（lockForUpdate）
     * 锁定账户行后重算余额并校验充足性，余额不足抛出 RuntimeException 令整个事务回滚，
     * 因此绝不会扣成负数，也绝不会出现没扣钱但写了流水的脏数据。
     */
    public function handle(AiUsageRecorded $event): void
    {
        // 元（浮点）到毫（整数）的精确换算：
        // 1. sprintf 将浮点元定点化为 6 位小数字符串（与 ai_usage_deductions.cost 的精度一致）；
        // 2. 去掉小数点得到微毫整数（1 元 = 1000000 微毫）；
        // 3. 除以 1000 向上取整得到毫。例：0.003 元 → 0.003000 → 3000 微毫 → 3 毫。
        $microMills = (int) preg_replace('/[^0-9]/', '', sprintf('%.6f', $event->cost));
        if ($microMills <= 0) {
            throw new RuntimeException('AI 用量扣费金额必须为正数，收到: '.$event->cost);
        }
        $millis = (int) ceil($microMills / 1000); // 1 毫 = 1000 微毫

        DB::transaction(function () use ($event, $millis): void {
            // 真实扣款：悲观锁 + 余额校验，不足时抛异常回滚整个事务（含下方流水写入）
            $this->ledger->debit(
                'user',
                $event->userId,
                'balance',
                $millis,
                'ai_usage',
                $event->providerId,
            );

            // 扣费流水：与真实扣款同一事务，任一失败整体回滚
            DB::table('ai_usage_deductions')->insert([
                'user_id' => $event->userId,
                'provider_id' => $event->providerId,
                'cost' => $event->cost,
                'input_tokens' => $event->inputTokens,
                'output_tokens' => $event->outputTokens,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
