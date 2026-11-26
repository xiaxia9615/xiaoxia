<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminWalletController extends Controller
{
    /**
     * 钱包列表（联查用户邮箱，按 ID 倒序）.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $perPage = 20;
        $query = Wallet::query()
            ->with('user:id,email')
            ->orderByDesc('id');

        $wallets = $query->paginate($perPage);

        $list = collect($wallets->items())->map(fn (Wallet $w): array => [
            'id' => $w->id,
            'user_id' => $w->user_id,
            'user_email' => $w->user?->email,
            'balance' => (float) $w->balance,
            'frozen_balance' => (float) $w->frozen_balance,
            'created_at' => $w->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $wallets->total(),
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
                'per_page' => $wallets->perPage(),
            ],
        ]);
    }

    /**
     * 钱包交易记录列表.
     */
    public function apiTransactions(Request $request, int $id): JsonResponse
    {
        $perPage = 20;

        $wallet = Wallet::find($id);
        if ($wallet === null) {
            return response()->json(['code' => 1, 'msg' => 'not found'], 404);
        }

        $txs = WalletTransaction::query()
            ->where('wallet_id', $id)
            ->orderByDesc('id')
            ->paginate($perPage);

        $list = collect($txs->items())->map(fn (WalletTransaction $t): array => [
            'id' => $t->id,
            'wallet_id' => $t->wallet_id,
            'type' => $t->type,
            'amount' => (float) $t->amount,
            'description' => $t->description,
            'out_trade_no' => $t->out_trade_no,
            'created_at' => $t->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'wallet' => [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'balance' => (float) $wallet->balance,
                    'frozen_balance' => (float) $wallet->frozen_balance,
                ],
                'list' => $list,
                'total' => $txs->total(),
                'current_page' => $txs->currentPage(),
                'last_page' => $txs->lastPage(),
                'per_page' => $txs->perPage(),
            ],
        ]);
    }
}
