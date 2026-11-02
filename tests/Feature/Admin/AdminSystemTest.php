<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_active' => 1, 'status' => 1])->save();
        return $admin;
    }

    public function test_admin_can_view_logs(): void
    {
        Http::fake();
        $this->actingAs($this->admin())->get('/admin/system/logs')->assertOk();
    }

    public function test_logs_are_escaped(): void
    {
        Http::fake();

        $logFile = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        $originalContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        
        // 修复：将恶意日志追加到文件末尾，而不是开头，确保页面能读到
        $maliciousLog = PHP_EOL . '[2026-11-02 21:00:00] testing.INFO: <script>alert("xss")</script>';
        file_put_contents($logFile, $originalContent . $maliciousLog);

        $response = $this->actingAs($this->admin())->get('/admin/system/logs');
        
        $response->assertOk();
        // 严格断言：HTML 中绝对不能出现未转义的 script 标签
        $response->assertDontSee('<script>alert("xss")</script>', false);
        // 严格断言：必须出现转义后的 &lt;script&gt;，证明转义逻辑生效
        $response->assertSee('&lt;script&gt;', false);

        // 清理现场
        file_put_contents($logFile, $originalContent);
    }

    public function test_non_admin_forbidden(): void
    {
        Http::fake();
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin/system/logs')->assertStatus(403);
    }
}
