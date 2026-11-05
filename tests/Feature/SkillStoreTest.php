<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Skill\Models\SkillDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Enums\SkillStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SkillStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_audit_skill()
    {
        // 1. 彻底禁用外部网络请求，防止测试卡 19 秒
        Http::fake();

        $owner = User::factory()->create();
        $other = User::factory()->create();
        
        // 强制覆盖所有的状态，确保是 Pending
        $product = Product::factory()->skill()->create(['user_id' => $owner->id]);
        $skill = SkillDetail::factory()->create([
            'product_id' => $product->id, 
            'status' => SkillStatus::Pending->value
        ]);

        // 非管理员发起审核，必须 403
        $this->actingAs($other, 'sanctum')
             ->postJson("/api/v1/admin/skills/{$skill->id}/audit", ['approve' => true])
             ->assertStatus(403);
    }

    public function test_admin_can_audit_any_skill()
    {
        Http::fake();

        $owner = User::factory()->create();
        
        // 1. 直接用 create 创建管理员，并强制覆盖 is_admin
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_active' => 1, 'status' => 1])->save();
        
        $product = Product::factory()->skill()->create(['user_id' => $owner->id]);
        $skill = SkillDetail::factory()->create([
            'product_id' => $product->id, 
            'status' => SkillStatus::Pending->value
        ]);

        // 2. 断言返回 200
        $response = $this->actingAs($admin, 'sanctum')
             ->postJson("/api/v1/admin/skills/{$skill->id}/audit", ['approve' => true]);
             
        $response->assertOk();
    }
}
