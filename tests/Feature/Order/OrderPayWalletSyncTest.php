<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // payOrder 不再扣款，只返回支付信息
        $result = $orderService->payOrder($order->id, $user->id);
        $this->assertArrayHasKey('pay_url', $result);

        // 钱包余额不变（支付前 10000，支付后仍 10000）
        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);

        // 订单状态不变（由异步回调更新）
        $this->assertEquals(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_pay_order_amount_below_minimum(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_2');
        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5]);
        // 直接创建毫单位小金额订单（绕过 createOrder 的分→毫 ×10），测试 payOrder 限额本身
        $order = Order::create([
            'order_no' => 'TEST'.date('YmdHis').random_int(1000, 9999),
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 5,
            'product_name' => $product->name,
            'product_snapshot' => ['id' => $product->id],
            'status' => 'pending',
        ]);

        try {
            $orderService->payOrder($order->id, $user->id);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('最低支付限额', $e->getMessage());
        }

        // 钱包余额不变
        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);
    }

    public function test_duplicate_pay_returns_payment_info(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_3');
        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        // 两次调用都返回支付信息，不扣款
        $result1 = $orderService->payOrder($order->id, $user->id);
        $this->assertArrayHasKey('pay_url', $result1);
        $this->assertWalletLedgerEqual($user->id);

        $result2 = $orderService->payOrder($order->id, $user->id);
        $this->assertArrayHasKey('pay_url', $result2);

        // 钱包余额不变
        $this->assertWalletLedgerEqual($user->id);
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);
    }

    public function test_pay_does_not_settle_commission(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_test_4');
        $product = Product::factory()->skill()->create(['status' => 1, 'price' => 5000]);
        $order = $orderService->createOrder($user->id, $product->id);

        $orderService->payOrder($order->id, $user->id);

        // payOrder 不再分账，由异步回调处理
        $this->assertDatabaseMissing('ledger_entries', ['biz_type' => 'order_commission']);
    }

    public function test_wallet_ledger_equal_after_multiple_operations(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($user, 10000, 'recharge_multi_1');
        $this->assertWalletLedgerEqual($user->id);

        // 支付不扣款，只返回支付信息
        $product1 = Product::factory()->skill()->create(['status' => 1, 'price' => 3000]);
        $order1 = $orderService->createOrder($user->id, $product1->id);
        $orderService->payOrder($order1->id, $user->id);
        $this->assertWalletLedgerEqual($user->id);

        $walletService->deposit($user, 5000, 'recharge_multi_2');
        $this->assertWalletLedgerEqual($user->id);

        $product2 = Product::factory()->skill()->create(['status' => 1, 'price' => 2000]);
        $order2 = $orderService->createOrder($user->id, $product2->id);
        $orderService->payOrder($order2->id, $user->id);
        $this->assertWalletLedgerEqual($user->id);

        // 最终余额 = 10000 + 5000 = 15000（支付不扣款）
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertSame(15000, (int) $wallet->balance);
        $this->assertSame(15000, app(LedgerContract::class)->balance('user', $user->id, 'balance'));
    }
}
