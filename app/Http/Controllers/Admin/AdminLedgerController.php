<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Ledger\Services\AdminLedgerService;
use Illuminate\Contracts\View\View;

final class AdminLedgerController extends Controller
{
    public function __construct(private readonly AdminLedgerService $service) {}

    /**
     * 账户列表 + 统计.
     */
    public function index(): View
    {
        $accounts = $this->service->paginateAccounts();

        // Controller 层整形行数组（x-table 组件只渲染 :rows，不输出插槽）
        $rows = $accounts->getCollection()->map(static fn ($a): array => [
            $a->id,
            e((string) $a->owner_type),
            $a->owner_id,
            e((string) $a->account_type),
            e((string) $a->currency),
            $a->balance,
            $a->entries_count,
            '<a href="'.route('admin.ledger.entries', $a->id).'" class="text-indigo-600 hover:text-indigo-800">查看流水</a>',
        ])->all();

        return view('admin.ledger.index', [
            'stats'    => $this->service->stats(),
            'accounts' => $accounts,
            'rows'     => $rows,
        ]);
    }

    /**
     * 某账户流水.
     *
     * 安全约束: 不读取任何 user_id 请求参数；
     * accountId 只用于定位流水，不用于『查看某用户』。
     */
    public function entries(int $accountId): View
    {
        $entries = $this->service->paginateEntries($accountId);

        $rows = $entries->getCollection()->map(static fn ($e): array => [
            e((string) $e->created_at),
            e((string) $e->transaction_id),
            e((string) $e->direction),
            $e->amount,
            e((string) $e->biz_type),
            $e->biz_id,
        ])->all();

        return view('admin.ledger.entries', [
            'accountId' => $accountId,
            'entries'   => $entries,
            'rows'      => $rows,
        ]);
    }
}
