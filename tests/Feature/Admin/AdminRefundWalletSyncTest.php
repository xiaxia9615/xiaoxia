<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRefundWalletSyncTest extends TestCase
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

    public function test_refund_syncs_buyer_wallet_balance(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($buyer, 10000, 'recharge_refund_test');
        $this->assertWalletLedgerEqual($buyer->id);

        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 5000,
            'status' => 1,
            'type' => 2,
        ]);

        $order = $orderService->createOrder($buyer->id, $product->id);
        $orderService->payOrder($order->id, $buyer->id);
        $this->assertWalletLedgerEqual($buyer->id);

        $wallet = Wallet::where('user_id', $buyer->id)->first();
        $this->assertSame(5000, (int) $wallet->balance);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund");
        $response->assertRedirect();

        $this->assertWalletLedgerEqual($buyer->id);
        $wallet = Wallet::where('user_id', $buyer->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);
        $this->assertSame(10000, app(LedgerContract::class)->balance('user', $buyer->id, 'balance'));

        $this->assertDatabaseHas('ledger_entries', [
            'biz_type' => 'order_refund',
            'biz_id' => $order->id,
        ]);
    }

    public function test_refund_idempotent(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($buyer, 10000, 'recharge_idempotent_test');
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 5000,
            'status' => 1,
            'type' => 2,
        ]);

        $order = $orderService->createOrder($buyer->id, $product->id);
        $orderService->payOrder($order->id, $buyer->id);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $walletBefore = Wallet::where('user_id', $buyer->id)->first();
        $ledgerBefore = app(LedgerContract::class)->balance('user', $buyer->id, 'balance');

        // 已退款订单状态为 Refunded，canRefund() 返回 false，控制器抛 RuntimeException
        try {
            $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund");
            $this->fail('Expected exception for duplicate refund');
        } catch (\RuntimeException $e) {
            // expected: 订单已退款或状态不可退款
        }

        $walletAfter = Wallet::where('user_id', $buyer->id)->first();
        $ledgerAfter = app(LedgerContract::class)->balance('user', $buyer->id, 'balance');

        $this->assertSame((int) $walletBefore->balance, (int) $walletAfter->balance);
        $this->assertSame($ledgerBefore, $ledgerAfter);
    }

    public function test_refund_does_not_touch_developer_wallet(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $walletService = app(WalletService::class);
        $orderService = app(OrderService::class);

        $walletService->deposit($buyer, 10000, 'recharge_dev_test');
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 5000,
            'status' => 1,
            'type' => 2,
        ]);

        $order = $orderService->createOrder($buyer->id, $product->id);
        $orderService->payOrder($order->id, $buyer->id);

        // 开发者分账后 ledger 有余额，但 wallets.balance 未同步（已知行为）
        $devLedger = app(LedgerContract::class)->balance('user', $seller->id, 'balance');
        $this->assertGreaterThan(0, $devLedger, '开发者应有分账收入');

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        // 退款后开发者 ledger 余额被扣回
        $devLedgerAfter = app(LedgerContract::class)->balance('user', $seller->id, 'balance');
        $this->assertSame(0, $devLedgerAfter, '开发者 ledger 余额应被扣回');

        // 开发者 wallets.balance 从未同步（已知行为，非期望行为，记录在案）
        $devWallet = Wallet::where('user_id', $seller->id)->first();
        if ($devWallet) {
            $this->assertSame(0, (int) $devWallet->balance, '开发者 wallet.balance 从未同步（已知行为）');
        }
    }
}
