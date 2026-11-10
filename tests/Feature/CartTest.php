<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Modules\Cart\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        $seller = User::factory()->create();

        return Product::query()->create([
            'user_id' => $seller->id,
            'type' => 2,
            'name' => '测试商品',
            'slug' => 'test-goods-'.uniqid(),
            'description' => '测试商品描述',
            'cover' => 'https://example.com/cover.jpg',
            'price' => 9900,
            'status' => 1,
        ]);
    }

    public function test_guest_cannot_access_cart_apis(): void
    {
        $this->getJson('/api/v1/cart/index')->assertUnauthorized();
        $this->postJson('/api/v1/cart/save', [])->assertUnauthorized();
        $this->postJson('/api/v1/cart/delete', ['id' => 1])->assertUnauthorized();
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct();

        $response = $this->postJson('/api/v1/cart/save', [
            'goods_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_again_increases_quantity(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct();

        $this->postJson('/api/v1/cart/save', ['goods_id' => $product->id, 'quantity' => 1]);
        $this->postJson('/api/v1/cart/save', ['goods_id' => $product->id, 'quantity' => 2]);

        $this->assertSame(
            1,
            CartItem::query()->where('user_id', $user->id)->count(),
            '同一商品应只有一条购物车记录'
        );
        $this->assertSame(
            3,
            (int) CartItem::query()->where('user_id', $user->id)->value('quantity'),
            '重复添加应累加数量'
        );
    }

    public function test_cart_index_returns_product_info(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct();

        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/v1/cart/index');

        $response->assertOk()->assertJsonPath('code', 0);
        $response->assertJsonPath('data.list.0.goods_id', $product->id);
        $response->assertJsonPath('data.list.0.goods_name', '测试商品');
        $response->assertJsonPath('data.list.0.image', 'https://example.com/cover.jpg');
        $response->assertJsonPath('data.list.0.price', 9900);
        $response->assertJsonPath('data.list.0.quantity', 1);
    }

    public function test_user_can_update_quantity_and_delete_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct();

        $item = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson('/api/v1/cart/save', ['id' => $item->id, 'quantity' => 5])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSame(5, (int) $item->refresh()->quantity);

        $this->postJson('/api/v1/cart/delete', ['id' => $item->id])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_user_cannot_touch_other_users_cart(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->createProduct();

        $item = CartItem::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($other);

        $this->postJson('/api/v1/cart/save', ['id' => $item->id, 'quantity' => 9])->assertNotFound();
        $this->postJson('/api/v1/cart/delete', ['id' => $item->id])->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }
}
