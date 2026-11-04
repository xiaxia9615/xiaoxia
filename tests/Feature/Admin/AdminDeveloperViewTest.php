<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\User\Enums\DeveloperStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDeveloperViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function normalUser(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    private function pendingDeveloper(): User
    {
        return User::factory()->create([
            'is_admin'         => false,
            'is_developer'     => false,
            'developer_status' => DeveloperStatus::Pending,
        ]);
    }

    // ────── 开发者审核页面 ──────

    public function test_admin_can_view_developers_page(): void
    {
        $dev = $this->pendingDeveloper();

        $this->actingAs($this->admin())
            ->get('/admin/developers')
            ->assertOk()
            ->assertSee($dev->email);
    }

    public function test_normal_user_forbidden_on_developers(): void
    {
        $this->actingAs($this->normalUser())
            ->get('/admin/developers')
            ->assertForbidden();
    }

    public function test_admin_can_approve_developer(): void
    {
        $dev = $this->pendingDeveloper();

        $this->actingAs($this->admin())
            ->post("/admin/developers/{$dev->id}/audit", ['action' => 'approve'])
            ->assertRedirect();

        $dev->refresh();
        $this->assertTrue((bool) $dev->is_developer);
        $this->assertSame(DeveloperStatus::Approved->value, $dev->developer_status->value);
    }

    public function test_admin_can_reject_developer(): void
    {
        $dev = $this->pendingDeveloper();

        $this->actingAs($this->admin())
            ->post("/admin/developers/{$dev->id}/audit", ['action' => 'reject'])
            ->assertRedirect();

        $dev->refresh();
        $this->assertFalse((bool) $dev->is_developer);
        $this->assertSame(DeveloperStatus::Rejected->value, $dev->developer_status->value);
    }

    // ────── 技能审核页面 ──────

    public function test_admin_can_view_skills_pending_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/skills/pending')
            ->assertOk();
    }

    public function test_normal_user_forbidden_on_skills_pending(): void
    {
        $this->actingAs($this->normalUser())
            ->get('/admin/skills/pending')
            ->assertForbidden();
    }

    // ────── 钱包管理页面 ──────

    public function test_admin_can_view_wallets_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/wallets')
            ->assertOk();
    }

    public function test_normal_user_forbidden_on_wallets(): void
    {
        $this->actingAs($this->normalUser())
            ->get('/admin/wallets')
            ->assertForbidden();
    }

    public function test_admin_can_view_wallet_recharges(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/wallets/recharges')
            ->assertOk();
    }
}
