<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminSkillTest extends TestCase
{
    use RefreshDatabase;

    private const FULL_KEY = 'sk-test-1234567890abcdef';

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeSkill(string $apiKey = self::FULL_KEY): int
    {
        $product = Product::factory()->skill()->create();

        $skill = SkillDetail::factory()->create([
            'product_id' => $product->id,
            'api_key'    => $apiKey,
        ]);

        return $skill->id;
    }

    public function test_admin_can_view_skills_index(): void
    {
        $skillId = $this->makeSkill();
        $skill = SkillDetail::find($skillId);
        $product = Product::find($skill->product_id);

        $this->actingAs($this->admin())
            ->get('/admin/skills')
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_skill_api_key_is_masked(): void
    {
        $this->makeSkill(self::FULL_KEY);

        $response = $this->actingAs($this->admin())
            ->get('/admin/skills');

        $response->assertOk();
        $response->assertDontSee(self::FULL_KEY, false);
        $response->assertSee('sk-****', false);
    }

    public function test_admin_can_view_skill_detail(): void
    {
        $skillId = $this->makeSkill();

        $this->actingAs($this->admin())
            ->get("/admin/skills/{$skillId}")
            ->assertOk()
            ->assertSee('分账比例');
    }

    public function test_admin_can_view_calls(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/skills/calls')
            ->assertOk();
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/skills')
            ->assertForbidden();
    }
}
