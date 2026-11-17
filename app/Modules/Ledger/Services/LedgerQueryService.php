<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Support\Facades\DB;

/**
 * Ledger 数据访问查询服务：集中封装 ledger_accounts / ledger_entries 的表操作。
 *
 * 仅在 Ledger 模块内部使用（LedgerService 注入），不对外暴露，
 * 业务规则（事务、悲观锁、复式记账、余额校验）保留在 LedgerService。
 */
final class LedgerQueryService
{
    /**
     * 查询账户 ID，不存在返回 null。
     */
    public function findAccountId(string $ownerType, int $ownerId, string $accountType): ?int
    {
        $id = DB::table('ledger_accounts')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('account_type', $accountType)
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * 查询或创建账户（currency 固定 CNY），返回账户 ID。
     */
    public function ensureAccount(string $ownerType, int $ownerId, string $accountType): int
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

    /**
     * 悲观锁查询账户行（须在事务内调用），不存在返回 null。
     *
     * @return object{id: int}|null
     */
    public function lockAccount(string $ownerType, int $ownerId, string $accountType): ?object
    {
        return DB::table('ledger_accounts')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('account_type', $accountType)
            ->lockForUpdate()
            ->first();
    }

    /**
     * 汇总账户余额（credit - debit），无流水返回 0。
     */
    public function sumBalance(int $accountId): int
    {
        $credit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'credit')->sum('amount');
        $debit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'debit')->sum('amount');

        return $credit - $debit;
    }

    /**
     * 插入单条流水。
     *
     * @param  array<string, mixed>  $entry
     */
    public function insertEntry(array $entry): void
    {
        DB::table('ledger_entries')->insert($entry);
    }

    /**
     * 批量插入流水（复式记账同一事务）。
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function insertEntries(array $entries): void
    {
        DB::table('ledger_entries')->insert($entries);
    }

    /**
     * 账户流水分页（paginator toArray 结构）。
     *
     * @return array<string, mixed>
     */
    public function paginateEntriesByAccount(int $accountId, int $perPage = 20): array
    {
        return DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->toArray();
    }
}
