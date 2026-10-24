<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'admin-access',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 1,
            'is_admin' => false,
        ]);
    }

    private function makeAdmin(string $email): User
    {
        $admin = $this->makeUser($email);
        $admin->update(['is_admin' => true]);

        return $admin;
    }

    // ─── 场景 1：未登录访客访问 /admin/dashboard，302 重定向 /login ───
    public function test_guest_is_redirected_to_login(): void
    {
        $res = $this->get('/admin/dashboard');

        $res->assertStatus(302);
        $this->assertSame(url('/login'), $res->headers->get('Location'));
    }

    // ─── 场景 2：普通用户访问 /admin/dashboard，403 ───
    public function test_normal_user_gets_403_on_dashboard(): void
    {
        $user = $this->makeUser('normal1@test.com');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(403);
    }

    // ─── 场景 3：管理员访问 /admin/dashboard，200 ───
    public function test_admin_can_view_dashboard(): void
    {
        $admin = $this->makeAdmin('admin1@test.com');

        $res = $this->actingAs($admin)->get('/admin/dashboard');

        $res->assertStatus(200);
    }

    // ─── 场景 4：普通用户访问 /admin/users，403 ───
    public function test_normal_user_gets_403_on_users(): void
    {
        $user = $this->makeUser('normal2@test.com');

        $res = $this->actingAs($user)->get('/admin/users');

        $res->assertStatus(403);
    }

    // ─── 场景 5：管理员访问 /admin/users，200 且含用户邮箱列表 ───
    public function test_admin_can_view_users_list(): void
    {
        $admin = $this->makeAdmin('admin2@test.com');
        $target = $this->makeUser('listed-user@test.com');

        $res = $this->actingAs($admin)->get('/admin/users');

        $res->assertStatus(200);
        $res->assertSee($target->email);
    }
}
