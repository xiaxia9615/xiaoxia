<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentNotifyTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(int $userId, int $amount, string $orderNo = 'PAY_TEST_001'): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $userId,
            'order_no' => $orderNo,
            'amount_in_cents' => $amount,
            'gateway' => 'mock',
            'status' => 'pending',
        ]);
    }

    public function test_notify_credits_balance_on_valid_callback(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user->id, 5000, 'PAY_NOTIFY_001');

        $response = $this->postJson('/api/v1/payment/notify/mock', [
            'order_no' => 'PAY_NOTIFY_001',
            'amount_in_cents' => 5000,
        ]);

        $response->assertOk()->assertJson(['code' => 'SUCCESS']);

        $order->refresh();
        $this->assertSame('paid', $order->status);

        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(5000, (int) $wallet->balance);
        $this->assertSame(5000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));
    }

    public function test_notify_is_idempotent(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user->id, 5000, 'PAY_NOTIFY_002');

        $payload = [
            'order_no' => 'PAY_NOTIFY_002',
            'amount_in_cents' => 5000,
        ];

        $response1 = $this->postJson('/api/v1/payment/notify/mock', $payload);
        $response1->assertOk()->assertJson(['code' => 'SUCCESS']);

        $response2 = $this->postJson('/api/v1/payment/notify/mock', $payload);
        $response2->assertOk()->assertJson(['code' => 'SUCCESS']);

        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(5000, (int) $wallet->balance, '余额应只增加一次');
        $this->assertSame(5000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));
    }

    public function test_notify_rejects_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user->id, 5000, 'PAY_NOTIFY_003');

        $response = $this->postJson('/api/v1/payment/notify/mock', [
            'order_no' => 'PAY_NOTIFY_003',
            'amount_in_cents' => 3000,
        ]);

        $response->assertOk()->assertJson(['code' => 'FAIL']);

        $order->refresh();
        $this->assertSame('pending', $order->status);

        $wallet = Wallet::where('user_id', $user->id)->first();
        if ($wallet) {
            $this->assertSame(0, (int) $wallet->balance, '余额不应变化');
        }
    }

    public function test_notify_rejects_unknown_order(): void
    {
        $response = $this->postJson('/api/v1/payment/notify/mock', [
            'order_no' => 'NOT_EXIST',
            'amount_in_cents' => 5000,
        ]);

        $response->assertOk()->assertJson(['code' => 'FAIL']);
    }

    public function test_notify_rejects_invalid_signature(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return new class implements PaymentGatewayInterface
            {
                public function transfer(string $account, int $amountInCents): bool
                {
                    return false;
                }

                public function createPayment(string $orderNo, int $amountInCents, string $subject): array
                {
                    return [];
                }

                public function verifyNotify(array $payload): array
                {
                    return ['verified' => false, 'order_no' => '', 'amount_in_cents' => 0];
                }
            };
        });

        $user = User::factory()->create();
        $this->createPendingOrder($user->id, 5000, 'PAY_NOTIFY_005');

        $response = $this->postJson('/api/v1/payment/notify/mock', [
            'order_no' => 'PAY_NOTIFY_005',
            'amount_in_cents' => 5000,
        ]);

        $response->assertOk()->assertJson(['code' => 'FAIL']);

        $wallet = Wallet::where('user_id', $user->id)->first();
        if ($wallet) {
            $this->assertSame(0, (int) $wallet->balance, '验签失败不应入账');
        }
    }
}
