<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Skill\Enums\SkillStatus;
use App\Modules\Skill\Models\SkillDetail;
use App\Modules\User\Enums\DeveloperStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DeveloperWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, bool $isAdmin = false, bool $isDeveloper = false): User
    {
        return User::create([
            'name' => 'test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'status' => 1,
            'is_admin' => $isAdmin,
            'is_active' => true,
            'is_developer' => $isDeveloper,
            'developer_status' => $isDeveloper ? DeveloperStatus::Approved->value : DeveloperStatus::None->value,
        ]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function makeUserWithToken(string $email, bool $isAdmin = false, bool $isDeveloper = false): array
    {
        $user = $this->makeUser($email, $isAdmin, $isDeveloper);
        $token = $user->createToken('test')->plainTextToken;

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

    // ─── 场景 1：非开发者创建技能失败 ───
    public function test_non_developer_cannot_create_skill(): void
    {
        [$user, $token] = $this->makeUserWithToken('nondev@test.com');

        $this->postJson('/api/v1/skills', $this->storePayload(), ['Authorization' => "Bearer {$token}"])
            ->assertStatus(400)
            ->assertJsonPath('code', 1);

        $this->assertSame(0, SkillDetail::where('product_id', '>', 0)->count());
    }

    // ─── 场景 2：开发者提审非本人技能失败（403）───
    public function test_developer_cannot_submit_others_skill(): void
    {
        [$owner, $ownerToken] = $this->makeUserWithToken('owner@test.com', false, true);

        $res = $this->postJson('/api/v1/skills', $this->storePayload(), ['Authorization' => "Bearer {$ownerToken}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $productId = $res->json('data.id');

        $this->assertSame(SkillStatus::Draft->value, $res->json('data.status'));

        [$other, $otherToken] = $this->makeUserWithToken('other-dev@test.com', false, true);

        $this->postJson("/api/v1/skills/{$productId}/submit", [], ['Authorization' => "Bearer {$otherToken}"])
            ->assertStatus(403)
            ->assertJsonPath('code', 1);

        // 状态应保持 draft，未被他人提审
        $detail = SkillDetail::where('product_id', $productId)->first();
        $this->assertSame(SkillStatus::Draft->value, $detail->status->value);
    }

    // ─── 场景 3：管理员审核成功（开发者申请 + 技能提审 + 管理员审核全链路）───
    public function test_admin_audit_developer_and_skill_success(): void
    {
        // 3.1 普通用户申请开发者
        [$user, $token] = $this->makeUserWithToken('applicant@test.com');

        $this->postJson('/api/v1/developers/apply', ['reason' => '希望上架技能'], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.developer_status', DeveloperStatus::Pending->value);

        $this->assertSame(DeveloperStatus::Pending->value, $user->fresh()->developer_status->value);

        // 3.2 管理员审核开发者申请
        [$admin, $adminToken] = $this->makeUserWithToken('admin@test.com', true);

        $this->postJson("/api/v1/admin/developers/{$user->id}/audit", ['approve' => true], ['Authorization' => "Bearer {$adminToken}"])
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.is_developer', true)
            ->assertJsonPath('data.developer_status', DeveloperStatus::Approved->value);

        $this->assertTrue((bool) $user->fresh()->is_developer);

        // 3.3 开发者创建技能草稿
        $res = $this->postJson('/api/v1/skills', $this->storePayload(), ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', SkillStatus::Draft->value);

        $productId = $res->json('data.id');

        // 3.4 开发者提审
        $this->postJson("/api/v1/skills/{$productId}/submit", [], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', SkillStatus::Pending->value);

        // 3.5 管理员审核技能通过
        $this->postJson("/api/v1/admin/skills/{$productId}/audit", ['approve' => true, 'note' => '内容合规'], ['Authorization' => "Bearer {$adminToken}"])
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.skill_status', SkillStatus::Published->value);

        $detail = SkillDetail::where('product_id', $productId)->first();
        $this->assertSame(SkillStatus::Published->value, $detail->status->value);
    }

    // ─── 场景 4：非管理员无法访问管理员审核接口（403）───
    public function test_non_admin_cannot_audit(): void
    {
        [$user, $token] = $this->makeUserWithToken('normal@test.com');

        $this->postJson("/api/v1/admin/developers/{$user->id}/audit", ['approve' => true], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(403);

        $this->postJson('/api/v1/admin/skills/1/audit', ['approve' => true], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(403);
    }

    // ─── 场景 5：重复申请开发者失败 ───
    public function test_duplicate_developer_apply_fails(): void
    {
        [$user, $token] = $this->makeUserWithToken('dup@test.com');

        $this->postJson('/api/v1/developers/apply', [], ['Authorization' => "Bearer {$token}"])
            ->assertOk();

        $this->postJson('/api/v1/developers/apply', [], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(400)
            ->assertJsonPath('code', 1);
    }
}
