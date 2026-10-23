<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SkillStoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, bool $isAdmin = false): User
    {
        return User::create([
            'name' => 'test',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 1,
            'is_admin' => $isAdmin,
        ]);
    }

    private function makeUserWithToken(string $email, bool $isAdmin = false): array
    {
        $user = $this->makeUser($email, $isAdmin);
        $plain = 'sk_'.uniqid();
        ApiKey::create([
            'user_id' => $user->id,
            'name' => 'test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash' => hash('sha256', $plain),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        return [$user, $token];
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test skill '.uniqid(),
            'description' => 'a test skill',
            'price' => 1000,
            'endpoint' => 'https://api.example.com/skill',
            'method' => 'POST',
        ], $overrides);
    }

    // ─── 场景 1：带 api_key + request_template 上架 → 存进去了，读出来能解密 ───

    public function test_store_with_api_key_and_request_template_persists_and_decrypts(): void
    {
        [$user, $token] = $this->makeUserWithToken('store1@test.com');

        $res = $this->postJson('/api/v1/skills', $this->storePayload([
            'api_key' => 'sk-secret-api-key-12345',
            'request_template' => ['model' => 'gpt-4', 'temperature' => 0.7],
        ]), ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $productId = $res->json('data.id');
        $this->assertNotNull($productId);

        // 从数据库读取，验证 api_key 已加密存储（原始值不等于明文）
        $detail = SkillDetail::where('product_id', $productId)->first();
        $this->assertNotNull($detail);

        // 通过 accessor 读取，验证能正确解密
        $this->assertEquals('sk-secret-api-key-12345', $detail->api_key);

        // 验证 request_template 正确存储
        $this->assertEquals(['model' => 'gpt-4', 'temperature' => 0.7], $detail->request_template);

        // 验证数据库中 api_key 是密文（不等于明文）
        $raw = SkillDetail::withoutGlobalScopes()->where('product_id', $productId)->first();
        $this->assertNotEquals('sk-secret-api-key-12345', $raw->getRawOriginal('api_key'));
    }

    // ─── 场景 2：不带 api_key 和 request_template → 也能成功（向后兼容） ───

    public function test_store_without_api_key_and_request_template_succeeds(): void
    {
        [$user, $token] = $this->makeUserWithToken('store2@test.com');

        $res = $this->postJson('/api/v1/skills', $this->storePayload(), [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $productId = $res->json('data.id');
        $this->assertNotNull($productId);

        $detail = SkillDetail::where('product_id', $productId)->first();
        $this->assertNotNull($detail);
        $this->assertNull($detail->api_key);
        $this->assertNull($detail->request_template);
    }

    // ─── 场景 3：非管理员不能审核技能 ───

    public function test_non_admin_cannot_audit_skill(): void
    {
        // 用户 A 上架技能
        [$userA, $tokenA] = $this->makeUserWithToken('owner@test.com');
        $res = $this->postJson('/api/v1/skills', $this->storePayload(), [
            'Authorization' => "Bearer {$tokenA}",
        ])->assertOk();
        $productId = $res->json('data.id');

        // 用户 B（非管理员）尝试审核 → 应被拒绝
        [$userB, $tokenB] = $this->makeUserWithToken('other@test.com');
        $this->patchJson("/api/v1/skills/{$productId}/audit", [
            'approve' => false,
            'note' => 'bad skill',
        ], ['Authorization' => "Bearer {$tokenB}"])
            ->assertStatus(403)
            ->assertJsonPath('code', 1);
    }

    // ─── 场景 4：管理员可以审核任何人的技能 ───

    public function test_admin_can_audit_any_skill(): void
    {
        // 用户 A 上架技能
        [$userA, $tokenA] = $this->makeUserWithToken('owner2@test.com');
        $res = $this->postJson('/api/v1/skills', $this->storePayload(), [
            'Authorization' => "Bearer {$tokenA}",
        ])->assertOk();
        $productId = $res->json('data.id');

        // 管理员审核
        $admin = $this->makeUser('admin2@test.com', true);
        $this->assertTrue($admin->is_admin, '管理员 is_admin 应为 true');
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/skills/{$productId}/audit", [
                'approve' => true,
            ])->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 1);
    }

    // ─── 场景 5：api_key 超长被拒绝 ───

    public function test_store_rejects_api_key_exceeding_max_length(): void
    {
        [$user, $token] = $this->makeUserWithToken('store3@test.com');

        $this->postJson('/api/v1/skills', $this->storePayload([
            'api_key' => str_repeat('x', 501),
        ]), ['Authorization' => "Bearer {$token}"])
            ->assertStatus(422);
    }

    // ─── 场景 6：request_template 非数组被拒绝 ───

    public function test_store_rejects_non_array_request_template(): void
    {
        [$user, $token] = $this->makeUserWithToken('store4@test.com');

        $this->postJson('/api/v1/skills', $this->storePayload([
            'request_template' => 'not-an-array',
        ]), ['Authorization' => "Bearer {$token}"])
            ->assertStatus(422);
    }
}
