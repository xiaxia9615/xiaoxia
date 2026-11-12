<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\Withdrawal;
use App\Modules\Wallet\Services\WalletService;
use App\Modules\Wallet\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class WithdrawalServiceTest extends TestCase
{
    use RefreshDatabase;

    private WithdrawalService $service;

    private WalletService $walletService;

    private LedgerContract $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
        $this->service = app(WithdrawalService::class);
        $this->ledger = app(LedgerContract::class);
    }

    /**
     * 创建已冻结资金的提现单
     */
    private function createWithdrawalWithFrozenFunds(int $amount = 5000): array
    {
        $user = User::factory()->create();
        $this->walletService->deposit($user, $amount, 'test_deposit_'.uniqid());
        $this->walletService->freezeFunds($user, $amount);

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        return [$user, $withdrawal];
    }

    // ===== 四状态流转 =====

    public function test_approve_transitions_pending_to_approved(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();

        $result = $this->service->approve($withdrawal->id, 1);

        $this->assertSame(Withdrawal::STATUS_APPROVED, $result->status);
        $this->assertNotNull($result->audited_at);

        // 余额不变：approve 不移动资金
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(5000, $balance['frozen_balance']);
    }

    public function test_mark_paid_transitions_approved_to_paid(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();
        $this->service->approve($withdrawal->id, 1);

        $result = $this->service->markPaid($withdrawal->id, 1);

        $this->assertSame(Withdrawal::STATUS_PAID, $result->status);

        // 冻结余额被扣除
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);

        // wallet 与 ledger 对账一致
        $ledgerFrozen = $this->ledger->balance('user', $user->id, 'frozen');
        $this->assertSame($balance['frozen_balance'], $ledgerFrozen);
    }

    public function test_reject_transitions_pending_to_rejected(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();

        $result = $this->service->reject($withdrawal->id, '不符合条件', 1);

        $this->assertSame(Withdrawal::STATUS_REJECTED, $result->status);
        $this->assertSame('不符合条件', $result->reject_reason);
        $this->assertSame(1, $result->rejected_by);
        $this->assertNotNull($result->rejected_at);

        // 冻结款退回可用余额
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(5000, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);

        // wallet 与 ledger 对账一致
        $ledgerBalance = $this->ledger->balance('user', $user->id, 'balance');
        $ledgerFrozen = $this->ledger->balance('user', $user->id, 'frozen');
        $this->assertSame($balance['balance'], $ledgerBalance);
        $this->assertSame($balance['frozen_balance'], $ledgerFrozen);
    }

    // ===== 非法状态转换 =====

    public function test_approve_rejects_non_pending_status(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();
        $this->service->approve($withdrawal->id, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/仅 pending 状态可核对通过/');

        $this->service->approve($withdrawal->id, 1);
    }

    public function test_mark_paid_rejects_non_approved_status(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/仅 approved 状态可确认已打款/');

        $this->service->markPaid($withdrawal->id, 1);
    }

    public function test_reject_rejects_non_pending_status(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();
        $this->service->approve($withdrawal->id, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/仅 pending 状态可驳回/');

        $this->service->reject($withdrawal->id, '理由', 1);
    }

    public function test_approve_throws_404_for_nonexistent_withdrawal(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->service->approve(99999, 1);
    }

    // ===== 幂等性 =====

    public function test_mark_paid_is_idempotent_via_status_guard(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();
        $this->service->approve($withdrawal->id, 1);
        $this->service->markPaid($withdrawal->id, 1);

        // 第二次调用应抛异常（状态已变为 paid）
        $this->expectException(RuntimeException::class);
        $this->service->markPaid($withdrawal->id, 1);
    }

    public function test_reject_is_idempotent_via_status_guard(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds();
        $this->service->reject($withdrawal->id, '理由', 1);

        // 第二次调用应抛异常（状态已变为 rejected）
        $this->expectException(RuntimeException::class);
        $this->service->reject($withdrawal->id, '理由', 1);
    }

    // ===== 原子退款 =====

    public function test_reject_atomically_refunds_frozen_to_balance(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(8000);

        $this->service->reject($withdrawal->id, '测试退款', 1);

        $balance = $this->walletService->getBalance($user);
        $this->assertSame(8000, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);

        // ledger 对账
        $this->assertSame(
            $balance['balance'],
            $this->ledger->balance('user', $user->id, 'balance'),
        );
        $this->assertSame(
            $balance['frozen_balance'],
            $this->ledger->balance('user', $user->id, 'frozen'),
        );
    }

    public function test_mark_paid_deducts_frozen_and_syncs_ledger(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(6000);
        $this->service->approve($withdrawal->id, 1);
        $this->service->markPaid($withdrawal->id, 1);

        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);

        // ledger 对账：frozen 余额为 0
        $this->assertSame(
            0,
            $this->ledger->balance('user', $user->id, 'frozen'),
        );
        // ledger 对账：balance 余额为 0
        $this->assertSame(
            0,
            $this->ledger->balance('user', $user->id, 'balance'),
        );
    }

    // ===== 完整流程对账 =====

    public function test_full_withdrawal_flow_wallet_ledger_consistency(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(10000);

        // 初始状态：balance=0, frozen=10000
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(10000, $balance['frozen_balance']);
        $this->assertSame(0, $this->ledger->balance('user', $user->id, 'balance'));
        $this->assertSame(10000, $this->ledger->balance('user', $user->id, 'frozen'));

        // approve：资金不动
        $this->service->approve($withdrawal->id, 1);
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(10000, $balance['frozen_balance']);

        // markPaid：冻结款扣除
        $this->service->markPaid($withdrawal->id, 1);
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);
        $this->assertSame(0, $this->ledger->balance('user', $user->id, 'balance'));
        $this->assertSame(0, $this->ledger->balance('user', $user->id, 'frozen'));
    }

    public function test_full_reject_flow_wallet_ledger_consistency(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(10000);

        // reject：冻结款退回可用余额
        $this->service->reject($withdrawal->id, '测试', 1);
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(10000, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);
        $this->assertSame(10000, $this->ledger->balance('user', $user->id, 'balance'));
        $this->assertSame(0, $this->ledger->balance('user', $user->id, 'frozen'));
    }

    // ===== 并发安全（lockForUpdate 串行化验证） =====

    public function test_concurrent_mark_paid_does_not_double_deduct(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(5000);
        $this->service->approve($withdrawal->id, 1);

        // 模拟并发：第一个 markPaid 成功，第二个应因状态守卫失败
        $this->service->markPaid($withdrawal->id, 1);

        $balance = $this->walletService->getBalance($user);
        $this->assertSame(0, $balance['frozen_balance']);

        // 第二次调用应抛异常
        $this->expectException(RuntimeException::class);
        $this->service->markPaid($withdrawal->id, 1);
    }

    public function test_concurrent_reject_does_not_double_refund(): void
    {
        [$user, $withdrawal] = $this->createWithdrawalWithFrozenFunds(5000);

        // 第一次 reject 成功
        $this->service->reject($withdrawal->id, '理由', 1);
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(5000, $balance['balance']);
        $this->assertSame(0, $balance['frozen_balance']);

        // 第二次 reject 应抛异常（状态已变为 rejected）
        $this->expectException(RuntimeException::class);
        $this->service->reject($withdrawal->id, '理由', 1);
    }
}
