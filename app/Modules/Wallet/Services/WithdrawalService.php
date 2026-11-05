<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class WithdrawalService
{
    public function __construct(
        private readonly LedgerContract $ledger,
        private readonly WalletService $walletService,
    ) {}

    /**
     * 审核通过提现（第一步：核对通过，不扣冻结款）。
     */
    public function approve(int $id, int $adminId): Withdrawal
    {
        $withdrawal = DB::transaction(function () use ($id): Withdrawal {
            $locked = Withdrawal::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                abort(404, '提现记录不存在。');
            }

            if ($locked->status !== Withdrawal::STATUS_PENDING) {
                throw new \RuntimeException("提现单 #{$id} 当前状态为 {$locked->status}，仅 pending 状态可核对通过。");
            }

            $locked->forceFill([
                'status' => Withdrawal::STATUS_APPROVED,
                'audited_at' => now(),
            ])->save();

            return $locked;
        });

        Log::info('finance.withdrawal.approved', [
            'withdrawal_id' => $withdrawal->id,
            'user_id' => $withdrawal->user_id,
            'amount' => $withdrawal->amount,
            'auditor_id' => $adminId,
        ]);

        return $withdrawal;
    }

    /**
     * 确认已打款（第二步：真实打款完成，扣除用户冻结款）。
     */
    public function markPaid(int $id, int $adminId): Withdrawal
    {
        $withdrawal = DB::transaction(function () use ($id): Withdrawal {
            $locked = Withdrawal::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                abort(404, '提现记录不存在。');
            }

            if ($locked->status !== Withdrawal::STATUS_APPROVED) {
                throw new \RuntimeException("提现单 #{$id} 当前状态为 {$locked->status}，仅 approved 状态可确认已打款。");
            }

            $this->ledger->debit(
                'user',
                (int) $locked->user_id,
                'frozen',
                (int) $locked->amount,
                'withdrawal_paid',
                (int) $locked->id,
            );

            $locked->forceFill([
                'status' => Withdrawal::STATUS_PAID,
            ])->save();

            return $locked;
        });

        Log::info('finance.withdrawal.paid', [
            'withdrawal_id' => $withdrawal->id,
            'user_id' => $withdrawal->user_id,
            'amount' => $withdrawal->amount,
            'operator_id' => $adminId,
        ]);

        return $withdrawal;
    }

    /**
     * 驳回提现（将冻结款退回可用余额，状态置为 rejected）。
     *
     * @throws \RuntimeException 当提现单不存在、状态非 pending、或退款失败时抛出
     */
    public function reject(int $id, string $reason, int $adminId): Withdrawal
    {
        $withdrawal = DB::transaction(function () use ($id, $reason, $adminId): Withdrawal {
            $locked = Withdrawal::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                abort(404, '提现记录不存在。');
            }

            if ($locked->status !== Withdrawal::STATUS_PENDING) {
                throw new \RuntimeException("提现单 #{$id} 当前状态为 {$locked->status}，仅 pending 状态可驳回。");
            }

            // 先更新状态，再退款；若退款抛异常则整个事务回滚
            $locked->forceFill([
                'status' => Withdrawal::STATUS_REJECTED,
                'reject_reason' => $reason,
                'rejected_by' => $adminId,
                'rejected_at' => now(),
            ])->save();

            $user = User::findOrFail($locked->user_id);
            $this->walletService->refundFunds($user, (int) $locked->amount);

            return $locked;
        });

        Log::info('finance.withdrawal.rejected', [
            'withdrawal_id' => $withdrawal->id,
            'user_id' => $withdrawal->user_id,
            'amount' => $withdrawal->amount,
            'reject_reason' => $reason,
            'operator_id' => $adminId,
        ]);

        return $withdrawal;
    }
}
