<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $email = 'admin-product@test.com'): User
    {
        $user = User::create([
            'name' => 'admin-product',
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
        $user->forceFill(['status' => 1, 'is_admin' => true])->save();

        return $user;
    }

    private function makeUser(string $email = 'normal-product@test.com'): User
    {
        $user = User::create([
            'name' => 'normal-product',
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
        $user->forceFill(['status' => 1, 'is_admin' => false])->save();

        return $user;
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->makeAdmin();

        $res = $this->actingAs($admin)->post('/admin/products', [
            'name' => '测试商品',
            'description' => '测试描述',
            'price' => 5000,
            'type' => 2,
            'status' => 1,
        ]);

        $res->assertStatus(302);
        $res->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => '测试商品',
            'description' => '测试描述',
            'price' => 5000,
            'type' => 2,
            'status' => 1,
            'user_id' => $admin->id,
        ]);
    }

    public function test_create_product_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();

        $res = $this->actingAs($admin)->postJson('/admin/products', []);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name', 'price', 'type', 'status']);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->makeAdmin();

        $product = Product::create([
            'user_id' => $admin->id,
            'type' => 1,
            'name' => '旧名称',
            'slug' => Str::slug('旧名称').'-'.uniqid(),
            'description' => '旧描述',
            'price' => 1000,
            'status' => 1,
        ]);

        $res = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => '新名称',
            'description' => '新描述',
            'price' => 3000,
            'type' => 2,
        ]);

        $res->assertStatus(302);
        $res->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('新名称', $product->name);
        $this->assertSame('新描述', $product->description);
        $this->assertSame(3000, $product->price);
        $this->assertSame(2, $product->type);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $admin = $this->makeAdmin();

        $product = Product::create([
            'user_id' => $admin->id,
            'type' => 1,
            'name' => '切换测试',
            'slug' => Str::slug('切换测试').'-'.uniqid(),
            'description' => null,
            'price' => 1000,
            'status' => 1,
        ]);

        $res = $this->actingAs($admin)->patch("/admin/products/{$product->id}/status");

        $res->assertStatus(302);

        $product->refresh();
        $this->assertSame(0, $product->status);

        // 再切回来
        $this->actingAs($admin)->patch("/admin/products/{$product->id}/status");
        $product->refresh();
        $this->assertSame(1, $product->status);
    }

    public function test_non_admin_cannot_create_product(): void
    {
        $user = $this->makeUser();

        $res = $this->actingAs($user)->post('/admin/products', [
            'name' => '越权创建',
            'description' => '不应成功',
            'price' => 1000,
            'type' => 1,
            'status' => 1,
        ]);

        $res->assertStatus(403);
    }

    public function test_create_product_writes_audit_log(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/products', [
            'name' => '审计测试商品',
            'description' => '审计描述',
            'price' => 2000,
            'type' => 1,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'operator_id' => $admin->id,
            'action' => 'admin.product.create',
            'target_type' => 'product',
        ]);
    }
}
