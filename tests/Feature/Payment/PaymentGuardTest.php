<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Modules\Payment\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_mock_route_accessible_in_testing(): void
    {
        $response = $this->postJson('/api/v1/payment/notify/mock', [
            'order_no' => 'PAY20260101120000TEST',
            'amount_in_cents' => 600,
        ]);

        // 订单不存在会返回 FAIL，但路由必须可达（不是 404）
        $this->assertNotSame(404, $response->status());
    }

    public function test_deposit_works_in_testing_with_mock(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/wallet/deposit', ['amount' => 5000]);

        $response->assertOk();
        $this->assertSame(1, PaymentOrder::where('user_id', $user->id)->count());
    }

    public function test_deposit_returns_503_in_production_with_mock(): void
    {
        config([
            'app.env' => 'production',
            'payment.default' => 'mock',
            'payment.allow_mock' => false,
        ]);

        $user = User::factory()->create();
        $before = PaymentOrder::where('user_id', $user->id)->count();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/wallet/deposit', ['amount' => 5000]);

        $response->assertStatus(503);
        $this->assertSame($before, PaymentOrder::where('user_id', $user->id)->count());
    }

    public function test_deposit_works_in_production_when_allow_mock_enabled(): void
    {
        config([
            'app.env' => 'production',
            'payment.default' => 'mock',
            'payment.allow_mock' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/wallet/deposit', ['amount' => 5000]);

        $response->assertOk();
    }
}
