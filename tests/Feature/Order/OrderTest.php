<?php

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['is_developer' => true]);
    }

    private function product(User $owner, int $price = 10000): Product
    {
        return Product::factory()->create([
            'user_id' => $owner->id,
            'price' => $price,
            'status' => 1,
            'type' => 2,
        ]);
    }

    private function giveBalance(User $user, int $amount): void
    {
        app(WalletService::class)->deposit($user, $amount, 'test_balance_'.mt_rand(1000, 999999));
    }

    public function test_create_order_requires_published_product(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);
        $product->update(['status' => 0]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/v1/orders', ['product_id' => $product->id])
            ->assertStatus(400);
    }

    public function test_create_order_success(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);

        $res = $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/v1/orders', ['product_id' => $product->id]);

        $res->assertOk();
        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'status' => OrderStatus::Pending->value,
            'amount' => $product->price,
        ]);
    }

    public function test_pay_order_success(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);
        $this->giveBalance($buyer, 50000);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 10000,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/pay")
            ->assertOk();

        // 实物商品支付后进入待发货（双态分流：physical -> pending_shipment）
        $this->assertEquals(OrderStatus::PendingShipment, $order->fresh()->status);
    }

    public function test_pay_order_insufficient_balance(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 10000,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/pay")
            ->assertStatus(400);
    }

    public function test_pay_order_idempotent(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);
        $this->giveBalance($buyer, 50000);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 10000,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();
        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();

        $balance = app(LedgerContract::class)->balance('user', $buyer->id, 'balance');
        $this->assertEquals(40000, $balance);
    }
}
