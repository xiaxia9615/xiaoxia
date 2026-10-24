<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 管理后台 Ledger 只读查询服务.
 *
 * 注意: ledger_accounts 表没有 balance 字段（B0 已确认），
 * 余额一律从 ledger_entries 按 direction 汇总（credit - debit）。
 */
final class AdminLedgerService
{
    /**
     * 账户列表 + 余额（entries 汇总）+ 流水数.
     */
    public function paginateAccounts(int $perPage = 20): LengthAwarePaginator
    {
        return DB::table('ledger_accounts as a')
            ->select('a.*')
            ->selectSub(function ($q): void {
                $q->from('ledger_entries')
                    ->whereColumn('account_id', 'a.id')
                    ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0)");
            }, 'balance')
            ->selectSub(function ($q): void {
                $q->from('ledger_entries')
                    ->whereColumn('account_id', 'a.id')
                    ->selectRaw('COUNT(*)');
            }, 'entries_count')
            ->orderByDesc('a.id')
            ->paginate($perPage);
    }

    /**
     * 某账户流水分页.
     */
    public function paginateEntries(int $accountId, int $perPage = 30): LengthAwarePaginator
    {
        return DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 统计: 账户数 / 流水数 / 总余额（按全部 entries 汇总）.
     */
    public function stats(): array
    {
        return [
            'accounts'      => DB::table('ledger_accounts')->count(),
            'entries'       => DB::table('ledger_entries')->count(),
            'total_balance' => (int) (DB::table('ledger_entries')
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) as t")
                ->value('t') ?? 0),
        ];
    }
}
