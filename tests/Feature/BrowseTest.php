<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Modules\Browse\Models\Browse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_browse_history(): void
    {
        $this->getJson('/api/v1/browse/index')->assertUnauthorized();
    }

    public function test_user_can_view_own_browse_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $seller = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'type' => 2,
            'name' => '测试商品',
            'slug' => 'test-'.uniqid(),
            'description' => '描述',
            'cover' => 'https://example.com/cover.jpg',
            'price' => 9900,
            'status' => 1,
        ]);

        Browse::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $response = $this->getJson('/api/v1/browse/index');

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame('测试商品', $response->json('data.list.0.product_name'));
    }

    public function test_user_cannot_see_others_history(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Browse::create(['user_id' => $owner->id, 'product_id' => 1]);

        Sanctum::actingAs($other);

        $response = $this->getJson('/api/v1/browse/index');

        $this->assertSame(0, $response->json('data.total'));
    }
}
