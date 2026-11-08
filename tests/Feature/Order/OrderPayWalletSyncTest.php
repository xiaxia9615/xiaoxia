<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderPayWalletSyncTest extends TestCase
{
    use RefreshDatabase;

    private function assertWalletLedgerEqual(int $userId): void
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        $ledgerBalance = app(LedgerContract::class)->balance('user', $userId, 'balance');
        $this->assertSame(
            (int) $wallet->balance,
            $ledgerBalance,
            'wallet 缓存与 ledger 余额不一致'
        );
    }

    public function test_recharge_and_pay_keep_wallet_and_ledger_in_sync(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_1');
        $this->assertWalletLedgerEqual($user->id);

        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        $orderService->payOrder($order->id, $user->id);

        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(5000, (int) $wallet->balance);
        $this->assertSame(5000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));

        $this->assertDatabaseHas('ledger_entries', ['biz_type' => 'order_commission']);
    }

    public function test_insufficient_balance_rolls_back_both(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 3000, 'recharge_test_2');
        $this->assertWalletLedgerEqual($user->id);

        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        try {
            $orderService->payOrder($order->id, $user->id);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(3000, (int) $wallet->balance);
        $this->assertSame(3000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));

        $order->refresh();
        $this->assertSame('pending', $order->status->value);
    }

    public function test_duplicate_pay_is_idempotent(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_3');
        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        $result1 = $orderService->payOrder($order->id, $user->id);
        $this->assertSame('success', $result1['status']);
        $this->assertWalletLedgerEqual($user->id);

        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(5000, (int) $wallet->balance);

        $result2 = $orderService->payOrder($order->id, $user->id);
        $this->assertSame('already_paid', $result2['status']);

        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(5000, (int) $wallet->balance);
    }

    public function test_commission_settled_after_pay(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_4');
        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        $orderService->payOrder($order->id, $user->id);

        $this->assertDatabaseHas('ledger_entries', ['biz_type' => 'order_commission']);

        $commissionTotal = (int) DB::table('ledger_entries')
            ->where('biz_type', 'order_commission')
            ->where('biz_id', $order->id)
            ->where('direction', 'credit')
            ->sum('amount');
        $this->assertSame(5000, $commissionTotal);
    }

    public function test_wallet_ledger_equal_after_multiple_operations(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        // 充值 10000
        $walletService->deposit($user, 10000, 'recharge_multi_1');
        $this->assertWalletLedgerEqual($user->id);

        // 支付 3000
        $product1 = Product::factory()->skill()->create(['status' => 1, 'price' => 3000]);
        $order1 = $orderService->createOrder($user->id, $product1->id);
        $orderService->payOrder($order1->id, $user->id);
        $this->assertWalletLedgerEqual($user->id);

        // 再充值 5000
        $walletService->deposit($user, 5000, 'recharge_multi_2');
        $this->assertWalletLedgerEqual($user->id);

        // 再支付 2000
        $product2 = Product::factory()->skill()->create(['status' => 1, 'price' => 2000]);
        $order2 = $orderService->createOrder($user->id, $product2->id);
        $orderService->payOrder($order2->id, $user->id);
        $this->assertWalletLedgerEqual($user->id);

        // 最终余额 = 10000 - 3000 + 5000 - 2000 = 10000
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);
        $this->assertSame(10000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));
    }
}
