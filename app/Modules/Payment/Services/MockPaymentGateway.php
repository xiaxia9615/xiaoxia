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
}
