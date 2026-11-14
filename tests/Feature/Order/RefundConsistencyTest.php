<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundConsistencyTest extends TestCase
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

    private function giveBalance(User $user, int $amount): void
    {
        app(WalletService::class)->deposit($user, $amount, 'test_balance_'.mt_rand(1000, 999999));
    }

    private function createPaidOrder(User $buyer, User $seller, int $price = 10000): Order
    {
        Http::fake();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => $price,
            'status' => 1,
            'type' => 2,
        ]);
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder($buyer->id, $product->id);
        $orderService->payOrder($order->id, $buyer->id);

        return Order::find($order->id);
    }

    /**
     * 场景1：退款后买家 wallet = ledger
     */
    public function test_refund_buyer_wallet_equals_ledger(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $this->assertWalletLedgerEqual($buyer->id);
        $this->assertSame(50000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
        $this->assertSame(50000, app(LedgerContract::class)->balance('user', $buyer->id, 'balance'));
    }

    /**
     * 场景2：支付后开发者 wallet = ledger（修复前 wallet 为 0 的关键场景）
     */
    public function test_pay_order_developer_wallet_equals_ledger(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $this->assertWalletLedgerEqual($seller->id);
        $this->assertSame(8000, (int) Wallet::where('user_id', $seller->id)->value('balance'));
        $this->assertSame(8000, app(LedgerContract::class)->balance('user', $seller->id, 'balance'));
    }

    /**
     * 场景3：退款后达人 wallet = ledger
     */
    public function test_refund_distributor_wallet_equals_ledger(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $distributor = User::factory()->create();

        DistributorRelation::create(['user_id' => $buyer->id, 'distributor_id' => $distributor->id]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $this->assertWalletLedgerEqual($distributor->id);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $this->assertWalletLedgerEqual($distributor->id);
        $this->assertSame(0, (int) Wallet::where('user_id', $distributor->id)->value('balance'));
        $this->assertSame(0, app(LedgerContract::class)->balance('user', $distributor->id, 'balance'));
    }

    /**
     * 场景4：达人 10% 被扣回
     */
    public function test_refund_deducts_distributor_commission(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $distributor = User::factory()->create();

        DistributorRelation::create(['user_id' => $buyer->id, 'distributor_id' => $distributor->id]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $distributorBalanceBefore = (int) Wallet::where('user_id', $distributor->id)->value('balance');
        $this->assertSame(1000, $distributorBalanceBefore);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $distributorBalanceAfter = (int) Wallet::where('user_id', $distributor->id)->value('balance');
        $this->assertSame(0, $distributorBalanceAfter);

        $this->assertDatabaseHas('ledger_entries', [
            'biz_type' => 'order_refund_commission',
            'biz_id' => $order->id,
        ]);
    }

    /**
     * 场景5：重复退款幂等
     */
    public function test_duplicate_refund_is_idempotent(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $walletBefore = (int) Wallet::where('user_id', $buyer->id)->value('balance');
        $ledgerBefore = app(LedgerContract::class)->balance('user', $buyer->id, 'balance');

        try {
            $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund");
            $this->fail('Expected exception for duplicate refund');
        } catch (\RuntimeException $e) {
            // expected: 订单已退款或状态不可退款
        }

        $walletAfter = (int) Wallet::where('user_id', $buyer->id)->value('balance');
        $ledgerAfter = app(LedgerContract::class)->balance('user', $buyer->id, 'balance');

        $this->assertSame($walletBefore, $walletAfter);
        $this->assertSame($ledgerBefore, $ledgerAfter);
    }

    /**
     * 场景6：达人已提现（余额 < share），退款被拒绝
     */
    public function test_refund_rejected_when_distributor_balance_insufficient(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $distributor = User::factory()->create();

        DistributorRelation::create(['user_id' => $buyer->id, 'distributor_id' => $distributor->id]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        // 达人佣金入账后余额 1000，模拟提现扣完
        $walletService = app(WalletService::class);
        $walletService->deduct($distributor, 1000, 'simulate_withdrawal_'.mt_rand(1000, 999999));

        $buyerBalanceBefore = (int) Wallet::where('user_id', $buyer->id)->value('balance');
        $distributorBalanceBefore = (int) Wallet::where('user_id', $distributor->id)->value('balance');
        $this->assertSame(0, $distributorBalanceBefore);

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund");
            $this->fail('Expected exception for insufficient distributor balance');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('达人佣金已提现', $e->getMessage());
        }

        // 买家余额不变
        $buyerBalanceAfter = (int) Wallet::where('user_id', $buyer->id)->value('balance');
        $this->assertSame($buyerBalanceBefore, $buyerBalanceAfter);

        // 达人余额不变
        $distributorBalanceAfter = (int) Wallet::where('user_id', $distributor->id)->value('balance');
        $this->assertSame($distributorBalanceBefore, $distributorBalanceAfter);

        // 订单状态不变，仍可退款
        $order->refresh();
        $this->assertTrue($order->status->canRefund());
    }

    /**
     * 场景7：无达人订单退款，正常退款无 order_refund_commission 流水
     */
    public function test_refund_without_distributor_succeeds(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);

        $this->giveBalance($buyer, 50000);
        $order = $this->createPaidOrder($buyer, $seller, 10000);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

        $this->assertDatabaseMissing('ledger_entries', [
            'biz_type' => 'order_refund_commission',
            'biz_id' => $order->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'biz_type' => 'order_refund',
            'biz_id' => $order->id,
        ]);
    }
}
