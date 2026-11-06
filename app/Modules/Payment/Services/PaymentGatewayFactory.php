<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

final class PaymentGatewayFactory
{
    public function make(): PaymentGatewayInterface
    {
        return match (config('payment.default')) {
            'mock' => new MockPaymentGateway,
            'wechat' => new WechatPayGateway(config('payment.wechat')),
            'alipay' => new AlipayGateway(config('payment.alipay')),
            default => throw new InvalidArgumentException('Unknown payment gateway: '.config('payment.default')),
        };
    }
}
