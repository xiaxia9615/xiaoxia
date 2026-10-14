<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class LedgerController
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function balance(Request $request): JsonResponse
    {
        $balance = $this->ledger->balance('user', $request->user()->id, 'balance');
        return response()->json(['code'=>0,'data'=>['balance'=>$balance]]);
    }

    public function entries(Request $request): JsonResponse
    {
        $accountId = DB::table('ledger_accounts')
            ->where('owner_type', 'user')
            ->where('owner_id', $request->user()->id)
            ->where('account_type', 'balance')
            ->value('id');

        if (! $accountId) {
            return response()->json(['code'=>0,'data'=>['items'=>[],'total'=>0]]);
        }

        $items = DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['code'=>0,'data'=>$items]);
    }
}
