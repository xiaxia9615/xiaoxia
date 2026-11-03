<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

interface PaymentGatewayInterface
{
    /**
     * 向指定账户转账。
     *
     * @param  string  $account  收款账户
     * @param  int  $amountInCents  金额（单位：分，整数）
     */
    public function transfer(string $account, int $amountInCents): bool;
}
