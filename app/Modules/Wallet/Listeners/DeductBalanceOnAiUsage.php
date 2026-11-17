<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Listeners;

use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use App\Modules\Ledger\Services\AiUsageDeductionService;
use App\Modules\User\Models\User;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class DeductBalanceOnAiUsage
{
    public function __construct(
        private WalletService $walletService,
        private AiUsageDeductionService $deductions,
    ) {}

    /**
     * AI 用量计费：经 WalletService::deduct 扣减用户余额（事务 + 悲观锁 + 幂等），
     * 同事务内写入扣费流水。
     *
     * 精度换算说明：
     * - 账本记账单位为毫（1 元 = 1000 毫），ledger_entries.amount 为整数存储；
     * - 事件携带的 cost 为浮点元（如 0.003 元），换算为 3 毫后可被整数精确表示，无精度丢失；
     * - 换算使用字符串定点运算（sprintf 后去小数点），避免二进制浮点误差；
     * - 换算结果向上取整到毫（ceil），保证平台不因舍入少收，单次误差不超过 1 毫（0.001 元）。
     */
    public function handle(AiUsageRecorded $event): void
    {
        $microMills = (int) preg_replace('/[^0-9]/', '', sprintf('%.6f', $event->cost));
        if ($microMills <= 0) {
            throw new RuntimeException('AI 用量扣费金额必须为正数，收到: '.$event->cost);
        }
        $millis = (int) ceil($microMills / 1000);

        DB::transaction(function () use ($event, $millis): void {
            $user = User::findOrFail($event->userId);

            $this->walletService->deduct(
                $user,
                $millis,
                'ai_usage_'.Str::uuid()->toString(),
            );

            $this->deductions->record(
                $event->userId,
                $event->providerId,
                $event->cost,
                $event->inputTokens,
                $event->outputTokens,
            );
        });
    }
}
