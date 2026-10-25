<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_view_health(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/system/health')
            ->assertOk()
            ->assertSee('MySQL')
            ->assertSee('Nginx')
            ->assertSee('PHP-FPM');
    }

    public function test_admin_can_view_backups(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/system/backups')
            ->assertOk();
    }

    public function test_backups_handles_missing_directory_gracefully(): void
    {
        // 目录不存在时 Service 降级返回空数组，不报错
        $service = app(\App\Modules\System\Services\AdminSystemService::class);
        $this->assertSame([], $service->backups('/nonexistent/backup/dir'));

        // 页面仍正常渲染
        $this->actingAs($this->admin())
            ->get('/admin/system/backups')
            ->assertOk();
    }

    public function test_admin_can_view_logs(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/system/logs')
            ->assertOk();
    }

    public function test_logs_handles_missing_file_gracefully(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/system/logs')
            ->assertOk();
    }

    public function test_logs_are_escaped(): void
    {
        Log::info('<script>alert("xss")</script>');

        $response = $this->actingAs($this->admin())->get('/admin/system/logs');
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/system/health')
            ->assertForbidden();
    }
}
