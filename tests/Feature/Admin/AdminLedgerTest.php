<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdminLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeAccount(int $ownerId = 1): int
    {
        return (int) DB::table('ledger_accounts')->insertGetId([
            'owner_type'   => 'user',
            'owner_id'     => $ownerId,
            'account_type' => 'balance',
            'currency'     => 'CNY',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function makeEntry(int $accountId, string $direction, int $amount): void
    {
        DB::table('ledger_entries')->insert([
            'transaction_id' => (string) Str::uuid(),
            'account_id'     => $accountId,
            'direction'      => $direction,
            'amount'         => $amount,
            'biz_type'       => 'test',
            'biz_id'         => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function test_admin_can_view_ledger_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/ledger')
            ->assertOk()
            ->assertSee('Ledger');
    }

    public function test_admin_can_view_account_entries(): void
    {
        $accountId = $this->makeAccount();
        $this->makeEntry($accountId, 'credit', 1000);
        $this->makeEntry($accountId, 'debit', 300);

        $this->actingAs($this->admin())
            ->get("/admin/ledger/{$accountId}/entries")
            ->assertOk()
            ->assertSee('1000')
            ->assertSee('300');
    }

    public function test_ledger_ignores_user_id_param(): void
    {
        $accountId = $this->makeAccount(1);
        $this->makeEntry($accountId, 'credit', 1000);

        // 传 ?user_id=999 不应改变账户列表内容
        $response = $this->actingAs($this->admin())
            ->get('/admin/ledger?user_id=999');

        $response->assertOk();
        // 断言页面仍显示 owner_id=1 的账户（而非被 user_id 参数过滤）
        $response->assertSee((string) $accountId);
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/ledger')
            ->assertForbidden();
    }
}
