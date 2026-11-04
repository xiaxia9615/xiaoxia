<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WalletService
{
    public function __construct(
        private readonly LedgerContract $ledger,
    ) {}

    public function deposit(User $user, int $amount, string $outTradeNo): array
    {
        return DB::transaction(function () use ($user, $amount, $outTradeNo) {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

            try {
                $transaction = WalletTransaction::create([
                    'out_trade_no' => $outTradeNo,
                    'wallet_id' => $wallet->id,
                    'type' => 'recharge',
                    'amount' => $amount,
                    'description' => '余额充值',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '23000') {
                    return ['status' => 'already_processed', 'message' => '订单已处理'];
                }
                throw $e;
            }

            $this->ledger->record('wallet_recharge', $transaction->id, [
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'balance', 'direction' => 'credit', 'amount' => $amount],
                ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'platform', 'direction' => 'debit', 'amount' => $amount],
            ]);

            $wallet->increment('balance', $amount);

            return ['status' => 'success', 'amount' => $amount];
        });
    }

    public function deduct(User $user, int $amount, string $outTradeNo): array
    {
        return DB::transaction(function () use ($user, $amount, $outTradeNo) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet || $wallet->balance < $amount) {
                throw new RuntimeException('余额不足');
            }

            try {
                $transaction = WalletTransaction::create([
                    'out_trade_no' => $outTradeNo,
                    'wallet_id' => $wallet->id,
                    'type' => 'deduct',
                    'amount' => $amount,
                    'description' => '余额扣除',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '23000') {
                    return ['status' => 'already_processed', 'message' => '订单已处理'];
                }
                throw $e;
            }

            $this->ledger->record('wallet_deduct', $transaction->id, [
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'balance', 'direction' => 'debit', 'amount' => $amount],
                ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'platform', 'direction' => 'credit', 'amount' => $amount],
            ]);

            $wallet->decrement('balance', $amount);

            return ['status' => 'success', 'amount' => $amount];
        });
    }

    /**
     * 冻结资金：可用余额 → 冻结余额
     */
    public function freezeFunds(User $user, int $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new RuntimeException('余额不足');
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('frozen_balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'freeze',
                'amount' => $amount,
                'description' => '资金冻结',
            ]);

            $this->ledger->record('wallet_freeze', $wallet->id, [
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'balance', 'direction' => 'debit', 'amount' => $amount],
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'frozen', 'direction' => 'credit', 'amount' => $amount],
            ]);
        });
    }

    /**
     * 结算扣款：从冻结余额中扣除
     */
    public function deductFunds(User $user, int $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->frozen_balance < $amount) {
                throw new RuntimeException('冻结余额不足');
            }

            $wallet->decrement('frozen_balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deduct',
                'amount' => $amount,
                'description' => '结算扣款',
            ]);

            $this->ledger->record('wallet_deduct_funds', $wallet->id, [
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'frozen', 'direction' => 'debit', 'amount' => $amount],
                ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'platform', 'direction' => 'credit', 'amount' => $amount],
            ]);
        });
    }

    /**
     * 退款：冻结余额退回到可用余额
     */
    public function refundFunds(User $user, int $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->frozen_balance < $amount) {
                throw new RuntimeException('冻结余额不足');
            }

            $wallet->decrement('frozen_balance', $amount);
            $wallet->increment('balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'refund',
                'amount' => $amount,
                'description' => '资金退款',
            ]);

            $this->ledger->record('wallet_refund', $wallet->id, [
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'frozen', 'direction' => 'debit', 'amount' => $amount],
                ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'balance', 'direction' => 'credit', 'amount' => $amount],
            ]);
        });
    }

    /**
     * 管理员分页查询所有钱包及其充值流水
     */
    public function paginateWallets(int $perPage = 20): LengthAwarePaginator
    {
        return Wallet::query()
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * 管理员分页查询所有充值流水
     */
    public function paginateRecharges(int $perPage = 20): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->with(['wallet.user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->where('type', 'recharge')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 获取用户余额
     *
     * @return array{balance: int, frozen_balance: int}
     */
    public function getBalance(User $user): array
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        return [
            'balance' => (int) $wallet->balance,
            'frozen_balance' => (int) $wallet->frozen_balance,
        ];
    }
}
