<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Skill\Models\SkillDetail;
use App\Modules\Skill\Enums\SkillStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_cannot_submit_others_skill()
    {
        $owner = User::factory()->create(['is_developer' => true]);
        $other = User::factory()->create(['is_developer' => true]);
        
        // 直接创建 SkillDetail，确保它的状态是 DRAFT
        $skill = SkillDetail::factory()->create([
            'status' => SkillStatus::Draft->value, // 关键点：状态必须是草稿
        ]);

        $this->actingAs($other, 'sanctum')
             ->postJson("/api/v1/skills/{$skill->id}/submit")
             ->assertStatus(403);
    }
}
