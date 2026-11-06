<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Withdrawal;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        $sensitiveKeys = ['is_admin', 'is_finance', 'is_super_admin', 'is_developer', 'is_active', 'status', 'balance'];
        $sensitive = array_intersect_key($attrs, array_flip($sensitiveKeys));
        $normal = array_diff_key($attrs, array_flip($sensitiveKeys));

        $user = User::create(array_merge([
            'name' => 'finance-test',
            'email' => uniqid('finance-', true).'@test.com',
            'password' => bcrypt('password123'),
        ], $normal));

        $user->forceFill(array_merge([
            'status' => 1,
            'is_admin' => false,
            'is_finance' => false,
            'is_super_admin' => false,
        ], $sensitive))->save();

        return $user;
    }

    // ─── 测试 1：普通管理员（is_admin=1, is_super_admin=0, is_finance=0）访问 GET /admin/finance，403 ───
    public function test_plain_admin_gets_403_on_finance_index(): void
    {
        $admin = $this->makeUser(['is_admin' => true]);

        $res = $this->actingAs($admin)->get('/admin/finance');
        $res->assertStatus(403);
    }

    // ─── 测试 2：普通管理员 POST 提现审核，403（中间件拦截 POST 请求） ───
    public function test_plain_admin_gets_403_on_approve_withdrawal_post(): void
    {
        $admin = $this->makeUser(['is_admin' => true]);
        $applicant = $this->makeUser();
        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $res = $this->actingAs($admin)->post("/admin/finance/withdrawals/{$withdrawal->id}/approve");
        $res->assertStatus(403);

        // 确认提现单未被审核通过
        $this->assertSame(
            Withdrawal::STATUS_PENDING,
            $withdrawal->fresh()->status,
        );
    }

    // ─── 测试 3：财务人员（is_finance=1）访问 GET /admin/finance，200 ───
    public function test_finance_user_can_view_finance_index(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);

        $res = $this->actingAs($finance)->get('/admin/finance');
        $res->assertStatus(200);
    }

    // ─── 测试 4：超级管理员（is_super_admin=1）访问 GET /admin/finance，200 ───
    public function test_super_admin_can_view_finance_index(): void
    {
        $superAdmin = $this->makeUser(['is_super_admin' => true]);

        $res = $this->actingAs($superAdmin)->get('/admin/finance');
        $res->assertStatus(200);
    }

    // ─── 补充：财务人员核对通过提现，状态流转为 approved ───
    public function test_finance_user_can_approve_pending_withdrawal(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();
        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $res = $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/approve");
        $res->assertRedirect(route('admin.finance.withdrawals'));

        $fresh = $withdrawal->fresh();
        $this->assertSame(Withdrawal::STATUS_APPROVED, $fresh->status);
        $this->assertNotNull($fresh->audited_at);
    }

    // ─── 补充：重复核对非 pending 状态提现被拦截 ───
    public function test_approving_non_pending_withdrawal_is_rejected(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();
        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_APPROVED,
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);

        $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/approve");
    }

    // ─── 补充：确认已转账（markPaid）扣除冻结款并流转为 paid ───
    public function test_finance_user_can_mark_paid_approved_withdrawal(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();
        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_APPROVED,
        ]);

        // 预置用户 frozen 账户余额（通过 LedgerContract 记账）
        app(LedgerContract::class)->record('test_setup', 0, [
            ['owner_type' => 'user', 'owner_id' => $applicant->id, 'account_type' => 'frozen', 'direction' => 'credit', 'amount' => 10000],
        ]);

        $res = $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/mark-paid");
        $res->assertRedirect(route('admin.finance.withdrawals'));

        $this->assertSame(Withdrawal::STATUS_PAID, $withdrawal->fresh()->status);

        // 冻结款已被扣除，余额归零
        $balance = app(LedgerContract::class)->balance('user', $applicant->id, 'frozen');
        $this->assertSame(0, $balance);
    }

    // ─── 补充：对非 approved 状态提现执行 markPaid 被拦截 ───
    public function test_marking_paid_non_approved_withdrawal_is_rejected(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();
        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);

        $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/mark-paid");
    }

    // ─── 驳回提现：财务人员成功驳回 pending 提现并退回冻结款 ───
    public function test_finance_user_can_reject_pending_withdrawal_and_refund_balance(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();

        Wallet::create([
            'user_id' => $applicant->id,
            'balance' => 0,
            'frozen_balance' => 10000,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $res = $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/reject", [
            'reject_reason' => '信息不完整，请补充',
        ]);

        $res->assertRedirect(route('admin.finance.withdrawals'));

        $fresh = $withdrawal->fresh();
        $this->assertSame(Withdrawal::STATUS_REJECTED, $fresh->status);
        $this->assertSame('信息不完整，请补充', $fresh->reject_reason);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertSame($finance->id, $fresh->rejected_by);

        $wallet = Wallet::where('user_id', $applicant->id)->first();
        $this->assertSame(10000, (int) $wallet->balance);
        $this->assertSame(0, (int) $wallet->frozen_balance);
    }

    // ─── 驳回非 pending 状态提现被拦截 ───
    public function test_reject_non_pending_withdrawal_fails(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();

        Wallet::create([
            'user_id' => $applicant->id,
            'balance' => 0,
            'frozen_balance' => 10000,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_APPROVED,
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);

        $this->actingAs($finance)->post("/admin/finance/withdrawals/{$withdrawal->id}/reject", [
            'reject_reason' => '测试驳回',
        ]);
    }

    // ─── 普通管理员（非 finance）驳回提现返回 403 ───
    public function test_regular_admin_cannot_reject_withdrawal(): void
    {
        $admin = $this->makeUser(['is_admin' => true]);
        $applicant = $this->makeUser();

        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $res = $this->actingAs($admin)->post("/admin/finance/withdrawals/{$withdrawal->id}/reject", [
            'reject_reason' => '测试驳回',
        ]);

        $res->assertStatus(403);

        $this->assertSame(
            Withdrawal::STATUS_PENDING,
            $withdrawal->fresh()->status,
        );
    }

    // ─── 驳回时退款失败则事务回滚（状态不变，资金不变） ───
    public function test_reject_is_atomic_when_refund_fails(): void
    {
        $finance = $this->makeUser(['is_finance' => true]);
        $applicant = $this->makeUser();

        // 钱包冻结余额为 0，退款时会因『冻结余额不足』抛异常
        Wallet::create([
            'user_id' => $applicant->id,
            'balance' => 0,
            'frozen_balance' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $applicant->id,
            'amount' => 10000,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        try {
            $this->withoutExceptionHandling()
                ->actingAs($finance)
                ->post("/admin/finance/withdrawals/{$withdrawal->id}/reject", [
                    'reject_reason' => '测试驳回',
                ]);
        } catch (\RuntimeException $e) {
            // 预期异常：退款失败
        }

        // 事务回滚：状态仍为 pending
        $this->assertSame(
            Withdrawal::STATUS_PENDING,
            $withdrawal->fresh()->status,
        );

        // 资金不变
        $wallet = Wallet::where('user_id', $applicant->id)->first();
        $this->assertSame(0, (int) $wallet->balance);
        $this->assertSame(0, (int) $wallet->frozen_balance);
    }
}
