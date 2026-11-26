<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Ledger\Services\AdminLedgerQueryService;
use Illuminate\Http\JsonResponse;

final class AdminLedgerController extends Controller
{
    public function __construct(
        private readonly AdminLedgerQueryService $query,
    ) {}

    /**
     * 账户列表（含余额汇总与流水数）.
     */
    public function apiIndex(): JsonResponse
    {
        $accounts = $this->query->paginateAccounts(20);

        $list = collect($accounts->items())->map(fn ($a): array => [
            'id' => $a->id,
            'owner_type' => $a->owner_type,
            'owner_id' => $a->owner_id,
            'account_type' => $a->account_type,
            'currency' => $a->currency,
            'balance' => (int) $a->balance,
            'entries_count' => (int) $a->entries_count,
            'created_at' => $a->created_at,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $accounts->total(),
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
            ],
        ]);
    }

    /**
     * 某账户流水分页.
     */
    public function apiEntries(int $id): JsonResponse
    {
        $entries = $this->query->paginateEntries($id, 30);

        $list = collect($entries->items())->map(fn ($e): array => [
            'id' => $e->id,
            'transaction_id' => $e->transaction_id,
            'account_id' => $e->account_id,
            'direction' => $e->direction,
            'amount' => (int) $e->amount,
            'biz_type' => $e->biz_type,
            'biz_id' => $e->biz_id,
            'created_at' => $e->created_at,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $entries->total(),
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
            ],
        ]);
    }

    /**
     * 统计概览.
     */
    public function apiStats(): JsonResponse
    {
        $stats = $this->query->stats();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'accounts' => $stats['accounts'],
                'entries' => $stats['entries'],
                'total_balance' => $stats['total_balance'],
            ],
        ]);
    }
}
