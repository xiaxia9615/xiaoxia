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
     * 双态商品分流测试：
     * - 场景 A：实物商品支付成功 -> 订单进入待发货（pending_shipment）；
     * - 场景 B：虚拟商品支付成功 -> 订单完成（completed）且买家钱包额度增加 quota。
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

        /** 场景 A：购买实物商品 -> 支付成功 -> 订单状态为待发货 */
        public function test_pay_physical_order_enters_pending_shipment(): void
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
            $orderService->payOrder($order->id, $buyer->id);

            $paid = Order::find($order->id);
            $this->assertSame(OrderStatus::PendingShipment, $paid->status);
            $this->assertNotNull($paid->paid_at);
            $this->assertNull($paid->tracking_no);

            // 实物订单不自动发货：买家余额只被扣款，无额外额度入账
            $this->assertSame(40000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
            $this->assertWalletLedgerEqual($buyer->id);
        }

        /** 场景 B：购买虚拟商品 -> 支付成功 -> 订单完成且钱包额度增加 */
        public function test_pay_virtual_order_completes_and_credits_quota(): void
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
            $orderService->payOrder($order->id, $buyer->id);

            $paid = Order::find($order->id);
            $this->assertSame(OrderStatus::Completed, $paid->status);
            $this->assertNotNull($paid->paid_at);

            // 扣款 10000 + 自动发货入账 3000 = 43000
            $this->assertSame(43000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
            $this->assertWalletLedgerEqual($buyer->id);

            // 幂等：重复支付不重复扣款、不重复加额度
            $again = $orderService->payOrder($order->id, $buyer->id);
            $this->assertSame('already_paid', $again['status']);
            $this->assertSame(43000, (int) Wallet::where('user_id', $buyer->id)->value('balance'));
        }
    }
