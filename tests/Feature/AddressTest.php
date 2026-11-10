<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Address\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_address_apis(): void
    {
        $this->getJson('/api/v1/address/index')->assertUnauthorized();
        $this->postJson('/api/v1/address/save', [])->assertUnauthorized();
        $this->postJson('/api/v1/address/delete', ['id' => 1])->assertUnauthorized();
        $this->postJson('/api/v1/address/default', ['id' => 1])->assertUnauthorized();
    }

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/address/save', [
            'name' => '张三',
            'phone' => '13800138000',
            'province' => '广东省',
            'city' => '深圳市',
            'district' => '南山区',
            'detail' => '科技园 1 号楼',
            'is_default' => true,
        ]);

        $response->assertOk()->assertJsonPath('code', 0);
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'name' => '张三',
            'is_default' => true,
        ]);
    }

    public function test_set_default_address_resets_previous_default(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = Address::create([
            'user_id' => $user->id,
            'name' => '地址一',
            'phone' => '13800138000',
            'detail' => '地址一详情',
            'is_default' => true,
        ]);
        $second = Address::create([
            'user_id' => $user->id,
            'name' => '地址二',
            'phone' => '13800138001',
            'detail' => '地址二详情',
            'is_default' => false,
        ]);

        $this->postJson('/api/v1/address/default', ['id' => $second->id])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSame(false, $first->refresh()->is_default, '旧默认地址应被重置');
        $this->assertSame(true, $second->refresh()->is_default, '新地址应成为默认');
        $this->assertSame(
            1,
            Address::query()->where('user_id', $user->id)->where('is_default', true)->count(),
            '默认地址必须唯一'
        );
    }

    public function test_user_cannot_touch_other_users_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $address = Address::create([
            'user_id' => $owner->id,
            'name' => '机主地址',
            'phone' => '13800138000',
            'detail' => '机主地址详情',
            'is_default' => true,
        ]);

        Sanctum::actingAs($other);

        $this->postJson('/api/v1/address/default', ['id' => $address->id])->assertNotFound();
        $this->postJson('/api/v1/address/delete', ['id' => $address->id])->assertNotFound();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }
}
