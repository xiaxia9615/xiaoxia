<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_audit_skill()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = \App\Modules\Product\Models\Product::factory()->create(['user_id' => $owner->id]);
        $skill = SkillDetail::factory()->create(['product_id' => $product->id]);

        // 完全匹配真实路由 POST /api/v1/admin/skills/{id}/audit
        $this->actingAs($other, 'sanctum')
             ->postJson("/api/v1/admin/skills/{$skill->id}/audit", ['approve' => true])
             ->assertStatus(403);
    }

    public function test_admin_can_audit_any_skill()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $product = \App\Modules\Product\Models\Product::factory()->create(['user_id' => $owner->id]);
        $skill = SkillDetail::factory()->create(['product_id' => $product->id]);

        $this->actingAs($admin, 'sanctum')
             ->postJson("/api/v1/admin/skills/{$skill->id}/audit", ['approve' => true])
             ->assertOk();
    }
}
