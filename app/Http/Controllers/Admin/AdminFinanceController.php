<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AdminFinanceController extends Controller
{
    public function __construct(
        private readonly LedgerContract $ledger,
    ) {}

    /**
     * 财务中心首页：平台总充值额、今日新增充值、平台净资产、待处理提现数量。
     */
    public function index(): View
    {
        // 平台总充值额：钱包充值流水求和（单位：毫），null 时默认 0
        $totalRecharge = (int) (WalletTransaction::query()
            ->where('type', 'recharge')
            ->sum('amount') ?? 0);

        // 今日新增充值金额（单位：毫），null 时默认 0
        $todayRecharge = (int) (WalletTransaction::query()
            ->where('type', 'recharge')
            ->whereDate('created_at', today())
            ->sum('amount') ?? 0);

        // 平台净资产：统一通过 LedgerContract 查询，不直接读 wallet 表
        $netAsset = (int) ($this->ledger->balance('platform', 0, 'platform') ?? 0);

        // 待处理提现数量
        $pendingWithdrawals = (int) (Withdrawal::query()
            ->where('status', Withdrawal::STATUS_PENDING)
            ->count() ?? 0);

        return view('admin.finance.index', [
            'totalRecharge'      => $totalRecharge,
            'todayRecharge'      => $todayRecharge,
            'netAsset'           => $netAsset,
            'pendingWithdrawals' => $pendingWithdrawals,
        ]);
    }

    /**
     * 充值流水（分页，预加载用户信息）。
     */
    public function recharges(): View
    {
        $transactions = WalletTransaction::query()
            ->with(['wallet.user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->where('type', 'recharge')
            ->orderByDesc('id')
            ->paginate(20);

        $rows = $transactions->getCollection()->map(fn ($t): array => [
            $t->id,
            e((string) ($t->out_trade_no ?? '-')),
            e((string) ($t->wallet->user->name ?? '-')),
            e((string) ($t->wallet->user->email ?? '-')),
            number_format($t->amount / 1000, 2),
            e((string) $t->type),
            e((string) ($t->created_at ?? '-')),
        ])->all();

        return view('admin.finance.recharges', [
            'transactions' => $transactions,
            'rows'         => $rows,
        ]);
    }

    /**
     * 提现列表（分页，预加载用户信息）。
     */
    public function withdrawals(): View
    {
        $withdrawals = Withdrawal::query()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->orderByDesc('id')
            ->paginate(20);

        $rows = $withdrawals->getCollection()->map(fn ($w): array => [
            $w->id,
            e((string) ($w->user->name ?? '-')),
            e((string) ($w->user->email ?? '-')),
            number_format($w->amount / 1000, 2),
            e((string) $w->status),
            e((string) ($w->audited_at ?? '-')),
            e((string) ($w->created_at ?? '-')),
        ])->all();

        return view('admin.finance.withdrawals', [
            'withdrawals' => $withdrawals,
            'rows'        => $rows,
        ]);
    }

    /**
     * 审核通过提现（第一步：核对通过，不扣冻结款）。
     *
     * 并发安全：事务 + lockForUpdate 行锁，且仅允许 pending 状态通过，
     * 防止财务人员重复点击造成重复打款。
     */
    public function approveWithdrawal(int $id): RedirectResponse
    {
        $withdrawal = DB::transaction(function () use ($id): Withdrawal {
            /** @var Withdrawal|null $locked */
            $locked = Withdrawal::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                abort(404, '提现记录不存在。');
            }

            if ($locked->status !== Withdrawal::STATUS_PENDING) {
                throw new \RuntimeException("提现单 #{$id} 当前状态为 {$locked->status}，仅 pending 状态可核对通过。");
            }

            $locked->forceFill([
                'status'     => Withdrawal::STATUS_APPROVED,
                'audited_at' => now(),
            ])->save();

            return $locked;
        });

        Log::info('finance.withdrawal.approved', [
            'withdrawal_id' => $withdrawal->id,
            'user_id'       => $withdrawal->user_id,
            'amount'        => $withdrawal->amount,
            'auditor_id'    => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.withdrawals')
            ->with('success', "提现单 #{$withdrawal->id} 已核对通过，请完成线下转账后点击『确认已转账』。");
    }

    /**
     * 确认已转账（第二步：真实打款完成，扣除用户冻结款）。
     *
     * 并发安全：事务 + lockForUpdate 行锁，且仅允许 approved 状态执行，
     * 通过 LedgerContract 原子扣减用户 frozen 账户，防止重复扣款。
     */
    public function markPaid(int $id): RedirectResponse
    {
        $withdrawal = DB::transaction(function () use ($id): Withdrawal {
            /** @var Withdrawal|null $locked */
            $locked = Withdrawal::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                abort(404, '提现记录不存在。');
            }

            if ($locked->status !== Withdrawal::STATUS_APPROVED) {
                throw new \RuntimeException("提现单 #{$id} 当前状态为 {$locked->status}，仅 approved 状态可确认已转账。");
            }

            // 通过 LedgerContract 原子扣除用户冻结款（悲观锁 + 余额校验）
            $this->ledger->debit(
                'user',
                (int) $locked->user_id,
                'frozen',
                (int) $locked->amount,
                'withdrawal_paid',
                (int) $locked->id,
            );

            $locked->forceFill([
                'status' => Withdrawal::STATUS_PAID,
            ])->save();

            return $locked;
        });

        Log::info('finance.withdrawal.paid', [
            'withdrawal_id' => $withdrawal->id,
            'user_id'       => $withdrawal->user_id,
            'amount'        => $withdrawal->amount,
            'operator_id'   => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.withdrawals')
            ->with('success', "提现单 #{$withdrawal->id} 已确认转账并扣除冻结款。");
    }
}
