<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AdminOrderProductTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        $user = User::create([
            'name' => 'order-product-user',
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
        $user->forceFill(['status' => 1, 'is_admin' => false])->save();

        return $user;
    }

    private function makeAdmin(string $email): User
    {
        $admin = $this->makeUser($email);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    // ─── 场景 1：普通用户访问 /admin/orders，403 ───
    public function test_normal_user_gets_403_on_orders(): void
    {
        Http::fake();

        $user = $this->makeUser('op-normal-1@test.com');
        $res = $this->actingAs($user)->get('/admin/orders');
        $res->assertStatus(403);
    }

    // ─── 场景 2：普通用户访问 /admin/products，403 ───
    public function test_normal_user_gets_403_on_products(): void
    {
        Http::fake();

        $user = $this->makeUser('op-normal-2@test.com');
        $res = $this->actingAs($user)->get('/admin/products');
        $res->assertStatus(403);
    }

    // ─── 场景 3：管理员访问 /admin/orders，200 ───
    public function test_admin_can_view_orders(): void
    {
        Http::fake();

        $admin = $this->makeAdmin('op-admin-1@test.com');
        $res = $this->actingAs($admin)->get('/admin/orders');
        $res->assertStatus(200);
    }

    // ─── 场景 4：管理员访问 /admin/products，200 ───
    public function test_admin_can_view_products(): void
    {
        Http::fake();

        $admin = $this->makeAdmin('op-admin-2@test.com');
        $res = $this->actingAs($admin)->get('/admin/products');
        $res->assertStatus(200);
    }

    // ─── 场景 5：管理员退款已支付订单，状态变 Refunded，买家余额增加 ───
    public function test_admin_refund_paid_order(): void
    {
        Http::fake();

        $admin = $this->makeAdmin('op-admin-3@test.com');
        $buyer = $this->makeUser('op-buyer-1@test.com');
        $seller = $this->makeUser('op-seller-1@test.com');

        $product = Product::create([
            'user_id' => $seller->id,
            'type' => 1,
            'name' => '退款测试技能',
            'slug' => 'refund-test-skill-001',
            'description' => 'desc',
            'cover' => null,
            'price' => 5000,
            'status' => 1,
            'audit_note' => null,
        ]);

        $order = Order::create([
            'order_no' => '2026110400000001',
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'amount' => 5000,
            'product_name' => $product->name,
            'product_snapshot' => [],
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        /** @var LedgerContract $ledger */
        $ledger = app(LedgerContract::class);

        // 退款前买家余额为 0
        $balanceBefore = $ledger->balance('user', $buyer->id, 'balance');
        $this->assertSame(0, $balanceBefore);

        $res = $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund");
        $res->assertStatus(302);

        $order->refresh();
        $this->assertSame(OrderStatus::Refunded, $order->status);

        // 退款后买家余额增加订单金额（原路退回）
        $balanceAfter = $ledger->balance('user', $buyer->id, 'balance');
        $this->assertSame(5000, $balanceAfter);
    }

    // ─── 场景 6：管理员强制下架商品，状态变 2 ───
    public function test_admin_force_archive_product(): void
    {
        Http::fake();

        $admin = $this->makeAdmin('op-admin-4@test.com');
        $seller = $this->makeUser('op-seller-2@test.com');

        $product = Product::create([
            'user_id' => $seller->id,
            'type' => 1,
            'name' => '待下架技能',
            'slug' => 'pending-archive-skill-001',
            'description' => 'desc',
            'cover' => null,
            'price' => 1000,
            'status' => 1,
            'audit_note' => null,
        ]);

        $res = $this->actingAs($admin)->post("/admin/products/{$product->id}/force-archive");
        $res->assertStatus(302);

        $product->refresh();
        $this->assertSame(2, (int) $product->status);
    }
}
