<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Models\Order;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Services\WithdrawalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminFinanceController extends Controller
{
    public function __construct(
        private readonly LedgerContract $ledger,
        private readonly WithdrawalService $withdrawalService,
    ) {}

    /**
     * 财务中心首页：平台总充值额、今日数据、平台净资产、待处理提现。
     */
    public function index(): View
    {
        // 平台总充值额（单位：毫）
        $totalRecharge = (int) (WalletTransaction::query()
            ->where('type', 'recharge')
            ->sum('amount') ?? 0);

        // 今日新增充值金额（单位：毫）
        $todayRecharge = (int) (WalletTransaction::query()
            ->where('type', 'recharge')
            ->whereDate('created_at', today())
            ->sum('amount') ?? 0);

        // 今日新增提现金额（单位：毫）
        $todayWithdrawal = (int) (Withdrawal::query()
            ->whereDate('created_at', today())
            ->sum('amount') ?? 0);

        // 今日订单分账总额（已支付订单金额，单位：毫）
        $todayOrderSettlement = (int) (Order::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('amount') ?? 0);

        // 平台净资产：统一通过 LedgerContract 查询
        $netAsset = (int) ($this->ledger->balance('platform', 0, 'platform') ?? 0);

        // 待处理提现数量
        $pendingWithdrawals = (int) (Withdrawal::query()
            ->where('status', Withdrawal::STATUS_PENDING)
            ->count() ?? 0);

        return view('admin.finance.index', [
            'totalRecharge' => $totalRecharge,
            'todayRecharge' => $todayRecharge,
            'todayWithdrawal' => $todayWithdrawal,
            'todayOrderSettlement' => $todayOrderSettlement,
            'netAsset' => $netAsset,
            'pendingWithdrawals' => $pendingWithdrawals,
        ]);
    }

    /**
     * 充值流水（分页，预加载用户信息，支持日期筛选）。
     */
    public function recharges(Request $request): View
    {
        $transactions = WalletTransaction::query()
            ->with(['wallet.user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->where('type', 'recharge')
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('end_date')))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->all());

        $rows = $transactions->getCollection()->map(fn ($t): array => [
            $t->id,
            e((string) ($t->out_trade_no ?? '-')),
            e((string) ($t->wallet->user->name ?? '-')),
            e((string) ($t->wallet->user->email ?? '-')),
            number_format($t->amount / 1000, 2),
            e((string) $t->type),
            e($t->created_at?->format('Y-m-d H:i:s') ?? '-'),
        ])->all();

        return view('admin.finance.recharges', [
            'transactions' => $transactions,
            'rows' => $rows,
        ]);
    }

    /**
     * 提现列表（分页，预加载用户信息，支持日期筛选）。
     */
    public function withdrawals(Request $request): View
    {
        $withdrawals = Withdrawal::query()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('end_date')))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->all());

        $rows = $withdrawals->getCollection()->map(fn ($w): array => [
            $w->id,
            e((string) ($w->user->name ?? '-')),
            e((string) ($w->user->email ?? '-')),
            number_format($w->amount / 1000, 2),
            e((string) $w->status),
            e($w->audited_at?->format('Y-m-d H:i:s') ?? '-'),
            e($w->created_at?->format('Y-m-d H:i:s') ?? '-'),
        ])->all();

        return view('admin.finance.withdrawals', [
            'withdrawals' => $withdrawals,
            'rows' => $rows,
        ]);
    }

    /**
     * 导出充值流水 CSV。
     */
    public function exportRecharges(Request $request): StreamedResponse
    {
        $fileName = 'recharges_'.now()->format('Ymd').'.csv';

        $transactions = WalletTransaction::query()
            ->with(['wallet.user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->where('type', 'recharge')
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('end_date')))
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($transactions): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['时间', '用户邮箱', '交易号', '金额（元）', '类型']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->created_at ?? '',
                    $t->wallet->user->email ?? '',
                    $t->out_trade_no ?? '',
                    number_format($t->amount / 1000, 2),
                    $t->type,
                ]);
            }

            fclose($handle);
        }, $fileName);
    }

    /**
     * 导出提现流水 CSV。
     */
    public function exportWithdrawals(Request $request): StreamedResponse
    {
        $fileName = 'withdrawals_'.now()->format('Ymd').'.csv';

        $withdrawals = Withdrawal::query()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('end_date')))
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($withdrawals): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['时间', '用户邮箱', '提现单号', '金额（元）', '状态']);

            foreach ($withdrawals as $w) {
                fputcsv($handle, [
                    $w->created_at ?? '',
                    $w->user->email ?? '',
                    (string) $w->id,
                    number_format($w->amount / 1000, 2),
                    $w->status,
                ]);
            }

            fclose($handle);
        }, $fileName);
    }

    /**
     * 审核通过提现（第一步：核对通过，不扣冻结款）。
     */
    public function approveWithdrawal(int $id): RedirectResponse
    {
        $withdrawal = $this->withdrawalService->approve($id, (int) auth()->id());

        return redirect()
            ->route('admin.finance.withdrawals')
            ->with('success', "提现单 #{$withdrawal->id} 已核对通过，请完成线下转账后点击『确认已打款』。");
    }

    /**
     * 确认已打款（第二步：真实打款完成，扣除用户冻结款）。
     */
    public function markPaid(int $id): RedirectResponse
    {
        $withdrawal = $this->withdrawalService->markPaid($id, (int) auth()->id());

        return redirect()
            ->route('admin.finance.withdrawals')
            ->with('success', "提现单 #{$withdrawal->id} 已确认打款并扣除冻结款。");
    }

    /**
     * 驳回提现（退回冻结款到可用余额，状态置为 rejected）。
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:255'],
        ]);

        $withdrawal = $this->withdrawalService->reject(
            $id,
            $validated['reject_reason'],
            (int) auth()->id(),
        );

        return redirect()
            ->route('admin.finance.withdrawals')
            ->with('success', "提现单 #{$withdrawal->id} 已驳回，冻结款已退回可用余额。");
    }
}
