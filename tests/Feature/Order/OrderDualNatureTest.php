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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 双态商品支付测试：
 * payOrder 不再修改订单状态或扣款，只返回支付信息。
 * 订单状态由支付宝异步回调更新。
 */
class OrderDualNatureTest extends TestCase
{
    use RefreshDatabase;

    private function giveBalance(User $user, int $amount): void
    {
        app(WalletService::class)->deposit($user, $amount, 'test_balance_'.mt_rand(1000, 999999));
    }

    private function assertWalletLedgerEqual(int $userId): void
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        $ledgerBalance = app(LedgerContract::class)->balance('user', $userId, 'balance');
        $this->assertSame(
            (int) $wallet->balance,
            $ledgerBalance,
            'wallet 缓存与 ledger 余额不一致',
        );
    }

    /** 场景 A：购买实物商品 -> payOrder 返回支付信息，订单状态不变 */
    public function test_pay_physical_order_returns_payment_info(): void
    {
        Http::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 10000,
            'status' => 1,
            'type' => 2,
            'nature' => Product::NATURE_PHYSICAL,
        ]);

        $this->giveBalance($buyer, 50000);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder($buyer->id, $product->id);
        $result = $orderService->payOrder($order->id, $buyer->id);

        // 返回支付信息
        $this->assertArrayHasKey('pay_url', $result);

        $paid = Order::find($order->id);
        // 订单状态不变（由异步回调更新）
        $this->assertSame(OrderStatus::Pending, $paid->status);
        $this->assertNull($paid->paid_at);

        // 钱包余额不变（不再扣款）
        $this->assertSame(50000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
        $this->assertWalletLedgerEqual($buyer->id);
    }

    /** 场景 B：购买虚拟商品 -> payOrder 返回支付信息，订单状态不变 */
    public function test_pay_virtual_order_returns_payment_info(): void
    {
        Http::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 10000,
            'status' => 1,
            'type' => 1,
            'nature' => Product::NATURE_VIRTUAL,
            'quota' => 3000,
            'skill_id' => 9,
        ]);

        $this->giveBalance($buyer, 50000);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder($buyer->id, $product->id);
        $result = $orderService->payOrder($order->id, $buyer->id);

        // 返回支付信息
        $this->assertArrayHasKey('pay_url', $result);

        $paid = Order::find($order->id);
        // 订单状态不变（由异步回调更新）
        $this->assertSame(OrderStatus::Pending, $paid->status);
        $this->assertNull($paid->paid_at);

        // 钱包余额不变（不再扣款、不自动发货）
        $this->assertSame(50000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
        $this->assertWalletLedgerEqual($buyer->id);

        // 重复调用仍返回支付信息，不报错
        $again = $orderService->payOrder($order->id, $buyer->id);
        $this->assertArrayHasKey('pay_url', $again);
        $this->assertSame(50000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
    }
}
