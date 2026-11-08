<?php

namespace Tests\Feature\Distributor;

use App\Models\User;
use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function giveBalance(User $user, int $amount): void
    {
        app(WalletService::class)->deposit($user, $amount, 'test_balance_'.mt_rand(1000, 999999));
    }

    public function test_pay_order_without_distributor()
    {
        Http::fake();
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $product = Product::factory()->create(['user_id' => $seller->id, 'price' => 10000, 'status' => 1]);
        $this->giveBalance($buyer, 50000);

        $order = Order::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'amount' => 10000, 'status' => OrderStatus::Pending->value]);

        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();

        $ledger = app(LedgerContract::class);
        $this->assertEquals(8000, $ledger->balance('user', $seller->id, 'balance'));
        $this->assertEquals(-48000, $ledger->balance('platform', 0, 'platform'));
    }

    public function test_pay_order_with_distributor()
    {
        Http::fake();
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_developer' => true]);
        $distributor = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id, 'price' => 10000, 'status' => 1]);
        $this->giveBalance($buyer, 50000);
        DistributorRelation::create(['user_id' => $buyer->id, 'distributor_id' => $distributor->id]);

        $order = Order::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'amount' => 10000, 'status' => OrderStatus::Pending->value]);

        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();

        $ledger = app(LedgerContract::class);
        $this->assertEquals(8000, $ledger->balance('user', $seller->id, 'balance'));
        $this->assertEquals(1000, $ledger->balance('user', $distributor->id, 'balance'));
        $this->assertEquals(-49000, $ledger->balance('platform', 0, 'platform'));
    }
}
