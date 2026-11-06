<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use RuntimeException;

final class AlipayGateway implements PaymentGatewayInterface
{
    /**
     * @param  array{app_id: ?string, private_key: ?string, public_key: ?string, notify_url: ?string}  $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function transfer(string $account, int $amountInCents): bool
    {
        throw new RuntimeException('AlipayGateway::transfer not implemented');
    }

    public function createPayment(string $orderNo, int $amountInCents, string $subject): array
    {
        throw new RuntimeException('AlipayGateway::createPayment not implemented');
    }

    public function verifyNotify(array $payload): array
    {
        throw new RuntimeException('AlipayGateway::verifyNotify not implemented');
    }
}
