<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'login-test',
            'email' => uniqid('login-', true).'@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'is_admin' => false,
            'is_finance' => false,
            'is_super_admin' => false,
        ], $attrs));
    }

    // ─── ① 纯财务人员能登录成功 ───
    public function test_finance_only_user_can_login(): void
    {
        $user = $this->makeUser(['is_finance' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.finance.index'));
    }

    // ─── ② 纯财务人员访问 admin dashboard 返回 403 ───
    public function test_finance_only_user_gets_403_on_admin_dashboard(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);

        $response = $this->actingAs($finance)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    // ─── ③ 纯财务人员能访问 finance index 返回 200 ───
    public function test_finance_only_user_can_access_finance_index(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);

        $response = $this->actingAs($finance)->get('/admin/finance');
        $response->assertStatus(200);
    }

    // ─── ④ 管理员登录仍跳转 dashboard（回归） ───
    public function test_admin_login_still_redirects_to_dashboard(): void
    {
        $admin = $this->makeUser(['is_admin' => true]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    // ─── ⑤ 无角色普通用户登录被拒（回归） ───
    public function test_normal_user_without_roles_cannot_login(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ─── ⑥ 纯财务人员访问 /login 页面时重定向到财务中心 ───
    public function test_finance_user_show_page_redirects_to_finance(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);

        $response = $this->actingAs($finance)->get('/login');
        $response->assertRedirect(route('admin.finance.index'));
    }

    // ─── ⑦ 跟随重定向确认 finance 页面真是 200，不是 302→403 ───
    public function test_finance_user_follows_redirect_to_finance_page(): void
    {
        $user = $this->makeUser(['is_finance' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.finance.index'));

        // 关键：跟随重定向，确认 finance 页面真是 200，不是 302→403
        $this->actingAs($user)->get(route('admin.finance.index'))->assertStatus(200);
    }
}
