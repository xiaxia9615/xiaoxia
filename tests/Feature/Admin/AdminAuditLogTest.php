<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('审计日志');
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_audit_log_created_on_apikey_revoke(): void
    {
        $target = User::factory()->create();
        $plain = 'sk_'.uniqid();
        $keyId = DB::table('api_keys')->insertGetId([
            'user_id' => $target->id,
            'name' => 'audit-test-key',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash' => hash('sha256', $plain),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/admin/api-keys/{$keyId}")
            ->assertRedirect('/admin/api-keys');

        $log = DB::table('audit_logs')->where('action', 'admin.apikey.revoke')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, (int) $log->operator_id);
        $this->assertSame('api_key', $log->target_type);
        $this->assertSame($keyId, (int) $log->target_id);
    }

    public function test_audit_log_created_on_user_toggle(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/toggle")
            ->assertRedirect();

        $log = DB::table('audit_logs')->where('action', 'admin.toggle_active')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, (int) $log->operator_id);
        $this->assertSame('user', $log->target_type);
        $this->assertSame($target->id, (int) $log->target_id);
    }

    public function test_audit_logs_filterable_by_action(): void
    {
        DB::table('audit_logs')->insert([
            [
                'operator_id' => 1,
                'action' => 'admin.apikey.revoke',
                'target_type' => 'api_key',
                'target_id' => 1,
                'payload' => '[]',
                'ip' => '127.0.0.1',
                'user_agent' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'operator_id' => 1,
                'action' => 'admin.toggle_active',
                'target_type' => 'user',
                'target_id' => 2,
                'payload' => '[]',
                'ip' => '127.0.0.1',
                'user_agent' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/audit-logs?action=admin.apikey.revoke')
            ->assertOk();

        $response->assertSee('admin.apikey.revoke');
        $response->assertDontSee('admin.toggle_active');
    }
}
