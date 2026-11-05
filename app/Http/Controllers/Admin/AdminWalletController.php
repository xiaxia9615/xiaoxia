<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Contracts\View\View;

final class AdminWalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function index(): View
    {
        $wallets = $this->walletService->paginateWallets();

        $rows = $wallets->getCollection()->map(fn ($w): array => [
            $w->id,
            $w->user_id,
            e((string) ($w->user->name ?? '-')),
            e((string) ($w->user->email ?? '-')),
            number_format((int) $w->balance),
            number_format((int) $w->frozen_balance),
            e((string) ($w->updated_at ?? '-')),
        ])->all();

        return view('admin.wallets.index', [
            'wallets' => $wallets,
            'rows' => $rows,
        ]);
    }

    public function recharges(): View
    {
        $transactions = $this->walletService->paginateRecharges();

        $rows = $transactions->getCollection()->map(fn ($t): array => [
            $t->id,
            e((string) ($t->out_trade_no ?? '-')),
            e((string) ($t->wallet->user->name ?? '-')),
            e((string) ($t->wallet->user->email ?? '-')),
            number_format((int) $t->amount),
            e((string) $t->type),
            e((string) ($t->created_at ?? '-')),
        ])->all();

        return view('admin.wallets.recharges', [
            'transactions' => $transactions,
            'rows' => $rows,
        ]);
    }
}
