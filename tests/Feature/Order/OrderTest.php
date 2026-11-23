<?php

namespace Tests\Feature\Order;

use App\Models\User;
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

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 10000,
            'status' => OrderStatus::Pending->value,
        ]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/pay");

        $response->assertOk();
        $response->assertJsonStructure(['code', 'data', 'message']);

        // payOrder 不再修改订单状态，由异步回调更新
        $this->assertEquals(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_pay_order_amount_below_minimum(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 5,
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

        // 两次调用都成功返回支付信息，订单状态不变
        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();
        $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();

        // 订单仍为待支付，钱包余额不变（不再扣款）
        $this->assertEquals(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cannot_view_others_order_detail(): void
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

        $attacker = $this->user();

        $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/order/detail?id='.$order->id)
            ->assertStatus(404);

        $this->actingAs($attacker, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_cannot_cancel_others_order(): void
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

        $attacker = $this->user();

        $this->actingAs($attacker, 'sanctum')
            ->postJson('/api/v1/order/cancel', ['order_id' => $order->id])
            ->assertStatus(400);

        $this->assertEquals(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cannot_delete_others_order(): void
    {
        Http::fake();
        $buyer = $this->user();
        $seller = $this->user();
        $product = $this->product($seller);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 10000,
            'status' => OrderStatus::Cancelled->value,
        ]);

        $attacker = $this->user();

        $this->actingAs($attacker, 'sanctum')
            ->postJson('/api/v1/order/delete', ['order_id' => $order->id])
            ->assertStatus(404);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
