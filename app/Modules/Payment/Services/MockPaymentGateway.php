<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

final class MockPaymentGateway implements PaymentGatewayInterface
{
    public function transfer(string $account, int $amountInCents): bool
    {
        Log::info('MockPaymentGateway transfer', [
            'account' => $account,
            'amount_in_cents' => $amountInCents,
        ]);

        return true;
    }

    public function createPayment(string $orderNo, int $amountInCents, string $subject): array
    {
        Log::info('MockPaymentGateway createPayment', [
            'order_no' => $orderNo,
            'amount_in_cents' => $amountInCents,
            'subject' => $subject,
        ]);

        return [
            'pay_url' => 'https://mock.example.com/pay/'.$orderNo,
            'order_no' => $orderNo,
            'amount_in_cents' => $amountInCents,
        ];
    }

    public function verifyNotify(array $payload): array
    {
        Log::info('MockPaymentGateway verifyNotify', [
            'payload' => $payload,
        ]);

        return [
            'verified' => true,
            'order_no' => $payload['order_no'] ?? '',
            'amount_in_cents' => $payload['amount_in_cents'] ?? 6,
        ];
    }
}
