<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;
use RuntimeException;

final class PaymentGatewayFactory
{
    public function make(): PaymentGatewayInterface
    {
        $gateway = config('payment.default');

        if ($gateway === 'mock' && ! config('payment.allow_mock')) {
            throw new RuntimeException(
                'Mock payment gateway is disabled (ALLOW_MOCK_PAYMENT=false). '
                .'Set PAYMENT_GATEWAY to a real gateway in production.'
            );
        }

        return match ($gateway) {
            'mock' => new MockPaymentGateway,
            'wechat' => new WechatPayGateway,
            'alipay' => new AlipayGateway,
            default => throw new InvalidArgumentException('Unknown payment gateway: '.$gateway),
        };
    }
}
