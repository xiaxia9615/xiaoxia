<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeApiKey(int $userId, int $status = 1): int
    {
        $plain = 'sk_' . uniqid();
        return DB::table('api_keys')->insertGetId([
            'user_id'    => $userId,
            'name'       => 'test-key-' . uniqid(),
            'key_prefix' => substr($plain, 0, 8),
            'key_hash'   => hash('sha256', $plain),
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_view_api_keys(): void
    {
        $user = User::factory()->create();
        $this->makeApiKey($user->id);

        $this->actingAs($this->admin())
            ->get('/admin/api-keys')
            ->assertOk()
            ->assertSee('test-key');
    }

    public function test_key_hash_not_shown(): void
    {
        $user = User::factory()->create();
        $plain = 'sk_supersecret_' . uniqid();
        $hash = hash('sha256', $plain);

        DB::table('api_keys')->insert([
            'user_id'    => $user->id,
            'name'       => 'leak-test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash'   => $hash,
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/api-keys');
        $response->assertOk();
        $response->assertDontSee($hash, false);
        $response->assertDontSee($plain, false);
        $response->assertSee(substr($plain, 0, 8), false);
    }

    public function test_admin_can_revoke_key(): void
    {
        $user = User::factory()->create();
        $keyId = $this->makeApiKey($user->id, 1);

        $this->actingAs($this->admin())
            ->delete("/admin/api-keys/{$keyId}")
            ->assertRedirect('/admin/api-keys');

        $row = DB::table('api_keys')->where('id', $keyId)->first();
        $this->assertSame(0, (int) $row->status);
    }

    public function test_revoke_is_idempotent(): void
    {
        $user = User::factory()->create();
        $keyId = $this->makeApiKey($user->id, 1);

        $this->actingAs($this->admin())->delete("/admin/api-keys/{$keyId}");
        $this->actingAs($this->admin())->delete("/admin/api-keys/{$keyId}");

        $row = DB::table('api_keys')->where('id', $keyId)->first();
        $this->assertSame(0, (int) $row->status);
    }

    public function test_revoked_key_rejects_api_call(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->skill()->create();
        SkillDetail::factory()->create(['product_id' => $product->id]);

        $plain = 'sk_' . uniqid();
        $keyId = DB::table('api_keys')->insertGetId([
            'user_id'    => $user->id,
            'name'       => 'revoke-test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash'   => hash('sha256', $plain),
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 吊销前：key 通过前置检查，应走到余额检查（余额不足）而非 no api key
        $preResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/skills/{$product->id}/call", ['input' => ['text' => 'hi']]);
        $preResponse->assertStatus(400);
        $this->assertStringNotContainsString(
            'no api key',
            (string) $preResponse->json('message'),
            '吊销前不应被 key 前置检查拒绝'
        );

        // 吊销
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->delete("/admin/api-keys/{$keyId}");

        // 吊销后：key 前置检查应拒绝
        $postResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/skills/{$product->id}/call", ['input' => ['text' => 'hi']]);
        $postResponse->assertStatus(400);
        $this->assertSame(
            'no api key for this user',
            (string) $postResponse->json('message'),
            '吊销后应被 key 前置检查拒绝'
        );
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/api-keys')
            ->assertForbidden();
    }
}
