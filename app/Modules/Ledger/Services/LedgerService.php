<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Contracts\LedgerContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LedgerService implements LedgerContract
{
    public function record(string $bizType, int $bizId, array $splits): string
    {
        $transactionId = (string) Str::uuid();

        DB::transaction(function () use ($transactionId, $bizType, $bizId, $splits) {
            foreach ($splits as $s) {
                $accountId = $this->ensureAccount($s['owner_type'], $s['owner_id'], $s['account_type']);
                DB::table('ledger_entries')->insert([
                    'transaction_id' => $transactionId,
                    'account_id' => $accountId,
                    'direction' => $s['direction'],
                    'amount' => $s['amount'],
                    'biz_type' => $bizType,
                    'biz_id' => $bizId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return $transactionId;
    }

    /**
     * 查询账户流水分页（paginator toArray 结构），账户不存在返回 null
     *
     * @return array<string, mixed>|null
     */
    public function entriesByUser(int $userId, string $accountType = 'balance'): ?array
    {
        $accountId = DB::table('ledger_accounts')
            ->where('owner_type', 'user')
            ->where('owner_id', $userId)
            ->where('account_type', $accountType)
            ->value('id');
        if (! $accountId) {
            return null;
        }

        return DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->orderByDesc('id')
            ->paginate(20)
            ->toArray();
    }

    public function balance(string $ownerType, int $ownerId, string $accountType): int
    {
        $accountId = DB::table('ledger_accounts')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('account_type', $accountType)
            ->value('id');

        if (! $accountId) {
            return 0;
        }

        $credit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'credit')->sum('amount');
        $debit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'debit')->sum('amount');

        return $credit - $debit;
    }

    /**
     * 原子扣减账户余额（记账单位：毫，1 元 = 1000 毫）
     *
     * 并发安全：事务内先以悲观锁（lockForUpdate）锁定账户行，串行化同一账户的并发扣减；
     * 锁定后重算余额并校验充足性，不足时抛出 RuntimeException 令整个事务回滚，
     * 保证余额绝不被扣成负数，也绝不产生『没扣钱但写了流水』的脏数据。
     *
     * @throws \RuntimeException 账户不存在、扣减金额非法或余额不足
     */
    public function debit(string $ownerType, int $ownerId, string $accountType, int $amount, string $bizType, int $bizId): string
    {
        if ($amount <= 0) {
            throw new \RuntimeException('扣减金额必须为正整数（单位：毫）');
        }

        return DB::transaction(function () use ($ownerType, $ownerId, $accountType, $amount, $bizType, $bizId): string {
            // 悲观锁：锁定账户行，同一账户的并发扣减在此串行化
            $account = DB::table('ledger_accounts')
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->where('account_type', $accountType)
                ->lockForUpdate()
                ->first();
            if (! $account) {
                throw new \RuntimeException('账户不存在: '.$ownerType.'/'.$ownerId.'/'.$accountType);
            }
            $accountId = (int) $account->id;
            // 锁定后重算余额（credit - debit），读取并发事务已提交的最新流水
            $credit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'credit')->sum('amount');
            $debitSum = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'debit')->sum('amount');
            $balance = $credit - $debitSum;
            if ($balance < $amount) {
                throw new \RuntimeException('余额不足：当前 '.$balance.' 毫，需要 '.$amount.' 毫');
            }
            $transactionId = (string) Str::uuid();
            $now = now();
            // 复式记账：用户余额扣减（debit），平台账户等额收入（credit），账目保持平衡
            DB::table('ledger_entries')->insert([
                [
                    'transaction_id' => $transactionId,
                    'account_id' => $accountId,
                    'direction' => 'debit',
                    'amount' => $amount,
                    'biz_type' => $bizType,
                    'biz_id' => $bizId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'transaction_id' => $transactionId,
                    'account_id' => $this->ensureAccount('platform', 0, 'platform'),
                    'direction' => 'credit',
                    'amount' => $amount,
                    'biz_type' => $bizType,
                    'biz_id' => $bizId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            return $transactionId;
        });
    }

    private function ensureAccount(string $ownerType, int $ownerId, string $accountType): int
    {
        $id = DB::table('ledger_accounts')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('account_type', $accountType)
            ->where('currency', 'CNY')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('ledger_accounts')->insertGetId([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'account_type' => $accountType,
            'currency' => 'CNY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
