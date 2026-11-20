<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Contracts;

use App\Modules\User\Models\User;

/**
 * Wallet 模块对外契约。
 *
 * 跨模块涉及用户余额的调用必须经此接口，禁止直接依赖 WalletService。
 */
interface WalletContract
{
    /**
     * 充值：增加用户余额并写入 Ledger，out_trade_no 幂等。
     *
     * @return array<string, mixed>
     */
    public function deposit(User $user, int $amount, string $outTradeNo): array;

    /**
     * 扣款：减少用户余额并写入 Ledger，out_trade_no 幂等。
     *
     * @return array<string, mixed>
     */
    public function deduct(User $user, int $amount, string $outTradeNo): array;

    /**
     * 查询用户钱包余额。
     *
     * @return array<string, mixed>
     */
    public function getBalance(User $user): array;

    /**
     * 复式记账并同步 wallets.balance 缓存。
     *
     * Ledger 为权威账本，wallets.balance 为派生缓存，二者在同一事务内更新。
     *
     * @param  array<int, array{owner_type:string, owner_id:int, account_type:string, direction:string, amount:int}>  $splits
     * @return string 交易 ID（UUID）
     */
    public function record(string $bizType, int $bizId, array $splits): string;
}
