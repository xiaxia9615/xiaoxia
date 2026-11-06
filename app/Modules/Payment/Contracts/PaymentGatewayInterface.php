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

    /**
     * 创建支付订单，返回支付参数。
     *
     * @param  string  $orderNo  商户订单号
     * @param  int  $amountInCents  金额（单位：分）
     * @param  string  $subject  订单标题
     * @return array 各网关格式不同，键名不做约束
     */
    public function createPayment(string $orderNo, int $amountInCents, string $subject): array;

    /**
     * 验证回调通知签名并解析订单数据。
     *
     * @param  array  $payload  回调原始数据
     * @return array{verified: bool, order_no: string, amount_in_cents: int}
     */
    public function verifyNotify(array $payload): array;
}
