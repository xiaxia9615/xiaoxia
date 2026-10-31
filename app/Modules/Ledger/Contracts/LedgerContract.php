<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Contracts;

/**
 * Ledger 模块对外契约
 *
 * 只暴露外部模块（Skill / Team 等）需要的能力，保持接口最小化：
 * - record(): 复式记账（多条分录原子写入）
 * - balance(): 查询账户余额
 * - debit(): 原子扣减余额（悲观锁 + 余额校验）
 *
 * 账本记账单位为毫（1 元 = 1000 毫）。
 */
interface LedgerContract
{
    /**
     * 记录一笔复式记账交易（多条分录在同一事务内原子写入）。
     *
     * @param array<int, array{owner_type:string, owner_id:int, account_type:string, direction:string, amount:int}> $splits
     * @return string 交易ID（UUID）
     */
    public function record(string $bizType, int $bizId, array $splits): string;

    /**
     * 查询账户余额（credit - debit），账户不存在返回 0。
     */
    public function balance(string $ownerType, int $ownerId, string $accountType): int;

    /**
     * 原子扣减账户余额：事务内悲观锁锁定账户行，重算余额并校验充足性。
     *
     * @throws \RuntimeException 账户不存在、扣减金额非法或余额不足
     * @return string 交易ID（UUID）
     */
    public function debit(string $ownerType, int $ownerId, string $accountType, int $amount, string $bizType, int $bizId): string;
}
