<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LedgerService
{
    public function record(string $bizType, int $bizId, array $splits): string
    {
        $transactionId = (string) Str::uuid();

        DB::transaction(function () use ($transactionId, $bizType, $bizId, $splits) {
            foreach ($splits as $s) {
                $accountId = $this->ensureAccount($s['owner_type'], $s['owner_id'], $s['account_type']);
                DB::table('ledger_entries')->insert([
                    'transaction_id' => $transactionId,
                    'account_id'     => $accountId,
                    'direction'      => $s['direction'],
                    'amount'         => $s['amount'],
                    'biz_type'       => $bizType,
                    'biz_id'         => $bizId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });

        return $transactionId;
    }

    /**
     * 查询账户流水分页（paginator toArray 结构），账户不存在返回 null
     * @return array<string, mixed>|null
     */
    public function entriesByUser(int $userId, string $accountType = 'balance'): ?array
    {
        $accountId = DB::table('ledger_accounts')
            ->where('owner_type', 'user')
            ->where('owner_id', $userId)
            ->where('account_type', $accountType)
            ->value('id');
        if (! $accountId) return null;
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

        if (! $accountId) return 0;

        $credit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'credit')->sum('amount');
        $debit  = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'debit')->sum('amount');

        return $credit - $debit;
    }

    private function ensureAccount(string $ownerType, int $ownerId, string $accountType): int
    {
        $id = DB::table('ledger_accounts')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('account_type', $accountType)
            ->where('currency', 'CNY')
            ->value('id');

        if ($id) return (int) $id;

        return (int) DB::table('ledger_accounts')->insertGetId([
            'owner_type'   => $ownerType,
            'owner_id'     => $ownerId,
            'account_type' => $accountType,
            'currency'     => 'CNY',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
