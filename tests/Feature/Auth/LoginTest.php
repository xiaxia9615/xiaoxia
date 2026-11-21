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
        $sensitiveKeys = ['is_admin', 'is_finance', 'is_super_admin', 'is_developer', 'is_active', 'status', 'balance'];
        $sensitive = array_intersect_key($attrs, array_flip($sensitiveKeys));
        $normal = array_diff_key($attrs, array_flip($sensitiveKeys));

        $user = User::create(array_merge([
            'name' => 'login-test',
            'email' => uniqid('login-', true).'@test.com',
            'password' => bcrypt('password'),
        ], $normal));

        $user->forceFill(array_merge([
            'status' => 1,
            'is_admin' => false,
            'is_finance' => false,
            'is_super_admin' => false,
        ], $sensitive))->save();

        return $user;
    }

    // ─── API 登录测试（SPA 架构） ───

    /**
     * 管理员通过 API 登录，返回 token + user（含 is_admin=true）
     */
    public function test_admin_login_via_api_returns_token_and_role(): void
    {
        $admin = $this->makeUser(['is_admin' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 0);
        $response->assertJsonStructure([
            'code',
            'data' => [
                'user' => ['id', 'name', 'email', 'is_admin', 'is_finance'],
                'token',
            ],
        ]);
        $response->assertJsonPath('data.user.is_admin', true);
        $response->assertJsonPath('data.user.is_finance', false);
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * 财务人员通过 API 登录，返回 is_finance=true
     */
    public function test_finance_user_login_via_api_returns_finance_flag(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $finance->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.is_admin', false);
        $response->assertJsonPath('data.user.is_finance', true);
    }

    /**
     * 普通用户通过 API 登录，is_admin=false, is_finance=false
     */
    public function test_normal_user_login_via_api_returns_no_roles(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.is_admin', false);
        $response->assertJsonPath('data.user.is_finance', false);
    }

    /**
     * 错误密码返回 401
     */
    public function test_login_with_wrong_password_returns_401(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    /*
     * ─── 以下为旧版 HTML 登录测试，已废弃 ───
     *
     * 系统已重构为 React SPA + API 架构，登录不再走 POST /login HTML 表单，
     * 而是通过 POST /api/v1/auth/login API 返回 JSON。
     *
     * 原有测试方法：
     * - test_finance_only_user_can_login（POST /login → assertRedirect admin.finance.index）
     * - test_finance_only_user_gets_403_on_admin_dashboard（GET /admin/dashboard → 403）
     * - test_finance_only_user_can_access_finance_index（GET /admin/finance → 200）
     * - test_admin_login_still_redirects_to_dashboard（POST /login → assertRedirect admin.dashboard）
     * - test_normal_user_without_roles_cannot_login（POST /login → assertSessionHasErrors）
     * - test_finance_user_show_page_redirects_to_finance（GET /login → assertRedirect）
     * - test_finance_user_follows_redirect_to_finance_page（POST /login → assertRedirect + GET → 200）
     */
}
