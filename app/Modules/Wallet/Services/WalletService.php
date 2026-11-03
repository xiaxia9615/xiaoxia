<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WalletService
{
    /**
     * 冻结资金：可用余额减少，冻结余额增加，记录流水。
     * 并发安全：事务内 lockForUpdate 锁定钱包行，串行化同一钱包的并发操作。
     *
     * @param  int  $userId  用户 ID
     * @param  int  $amountInCents  金额（单位：分，整数）
     */
    public function freezeFunds(int $userId, int $amountInCents): void
    {
        $this->assertPositiveAmount($amountInCents);

        DB::transaction(function () use ($userId, $amountInCents): void {
            $wallet = $this->lockWallet($userId);

            $balanceInCents = $this->toCents((string) $wallet->balance);

            if ($balanceInCents < $amountInCents) {
                throw new RuntimeException('余额不足');
            }

            $wallet->update([
                'balance' => $this->toDecimal($balanceInCents - $amountInCents),
                'frozen_balance' => $this->toDecimal($this->toCents((string) $wallet->frozen_balance) + $amountInCents),
            ]);

            $this->recordTransaction($wallet->id, 'freeze', $amountInCents, '冻结资金');
        });
    }

    /**
     * 结算扣款：扣除冻结余额，记录流水。
     */
    public function deductFunds(int $userId, int $amountInCents): void
    {
        $this->assertPositiveAmount($amountInCents);

        DB::transaction(function () use ($userId, $amountInCents): void {
            $wallet = $this->lockWallet($userId);

            $frozenInCents = $this->toCents((string) $wallet->frozen_balance);

            if ($frozenInCents < $amountInCents) {
                throw new RuntimeException('冻结余额不足');
            }

            $wallet->update([
                'frozen_balance' => $this->toDecimal($frozenInCents - $amountInCents),
            ]);

            $this->recordTransaction($wallet->id, 'deduct', $amountInCents, '结算扣款');
        });
    }

    /**
     * 退款：冻结余额退回可用余额，记录流水。
     */
    public function refundFunds(int $userId, int $amountInCents): void
    {
        $this->assertPositiveAmount($amountInCents);

        DB::transaction(function () use ($userId, $amountInCents): void {
            $wallet = $this->lockWallet($userId);

            $frozenInCents = $this->toCents((string) $wallet->frozen_balance);

            if ($frozenInCents < $amountInCents) {
                throw new RuntimeException('冻结余额不足');
            }

            $wallet->update([
                'balance' => $this->toDecimal($this->toCents((string) $wallet->balance) + $amountInCents),
                'frozen_balance' => $this->toDecimal($frozenInCents - $amountInCents),
            ]);

            $this->recordTransaction($wallet->id, 'refund', $amountInCents, '冻结退回');
        });
    }

    /**
     * 充值：增加可用余额，记录流水。
     */
    public function deposit(int $userId, int $amountInCents): void
    {
        $this->assertPositiveAmount($amountInCents);

        DB::transaction(function () use ($userId, $amountInCents): void {
            $wallet = $this->lockWallet($userId);

            $wallet->update([
                'balance' => $this->toDecimal($this->toCents((string) $wallet->balance) + $amountInCents),
            ]);

            $this->recordTransaction($wallet->id, 'deposit', $amountInCents, '充值');
        });
    }

    /**
     * 查询钱包（不存在时自动创建），返回余额（分）。
     *
     * @return array{balance: int, frozen_balance: int}
     */
    public function getBalance(int $userId): array
    {
        $wallet = $this->ensureWallet($userId);

        return [
            'balance' => $this->toCents((string) $wallet->balance),
            'frozen_balance' => $this->toCents((string) $wallet->frozen_balance),
        ];
    }

    private function lockWallet(int $userId): Wallet
    {
        $wallet = Wallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($wallet !== null) {
            return $wallet;
        }

        // 钱包不存在：先创建（user_id 唯一索引兜底并发重复创建），再锁定读取
        try {
            Wallet::query()->create(['user_id' => $userId]);
        } catch (QueryException) {
            // 并发下已被其他事务创建，忽略后继续锁定读取
        }

        return Wallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureWallet(int $userId): Wallet
    {
        return Wallet::query()->where('user_id', $userId)->first()
            ?? Wallet::query()->create(['user_id' => $userId]);
    }

    private function recordTransaction(int $walletId, string $type, int $amountInCents, string $description): void
    {
        WalletTransaction::query()->create([
            'wallet_id' => $walletId,
            'type' => $type,
            'amount' => $this->toDecimal($amountInCents),
            'description' => $description,
        ]);
    }

    private function assertPositiveAmount(int $amountInCents): void
    {
        if ($amountInCents <= 0) {
            throw new RuntimeException('金额必须为正整数（分）');
        }
    }

    /**
     * 金额统一以整数（分）计算，仅在读写数据库时转换。
     * 转换全程使用字符串/整数运算，禁止 float，避免浮点精度问题。
     */
    private function toCents(string $decimalAmount): int
    {
        $parts = explode('.', $decimalAmount);
        $whole = (int) $parts[0];
        $frac = isset($parts[1])
            ? (int) substr(str_pad($parts[1], 2, '0', STR_PAD_RIGHT), 0, 2)
            : 0;

        return $whole * 100 + $frac;
    }

    private function toDecimal(int $amountInCents): string
    {
        $sign = $amountInCents < 0 ? '-' : '';
        $abs = abs($amountInCents);

        return $sign.intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }
}
