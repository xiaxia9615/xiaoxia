<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Modules\System\Services\AuditService;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminFinanceController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * 财务中心首页：平台总充值额、今日数据、平台净资产、待处理提现。
     */
    /**
     * 充值流水（分页，预加载用户信息，支持日期筛选）。
     */
    /**
     * 提现列表（分页，预加载用户信息，支持日期筛选）。
     */
    /**
     * 导出充值流水 CSV。
     */
    /**
     * 导出提现流水 CSV。
     */
    /**
     * 审核通过提现（第一步：核对通过，不扣冻结款）。
     */
    /**
     * 确认已打款（第二步：真实打款完成，扣除用户冻结款）。
     */
    /**
     * 驳回提现（退回冻结款到可用余额，状态置为 rejected）。
     */
    /**
     * 充值流水列表（JSON API，供 React SPA Admin 后台使用）。
     */
    public function apiRecharges(Request $request): JsonResponse
    {
        $transactions = WalletTransaction::query()
            ->with(['wallet.user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->where('type', 'recharge')
            ->orderByDesc('id')
            ->paginate(20);

        $list = $transactions->getCollection()->map(fn (WalletTransaction $t): array => [
            'id' => $t->id,
            'out_trade_no' => $t->out_trade_no,
            'user_name' => $t->wallet?->user?->name,
            'user_email' => $t->wallet?->user?->email,
            'amount' => $t->amount,
            'type' => $t->type,
            'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
            ],
        ]);
    }

    /**
     * 提现单列表（JSON API，供 React SPA Admin 后台使用）。
     */
    public function apiWithdrawals(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::query()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->orderByDesc('id')
            ->paginate(20);

        $list = $withdrawals->getCollection()->map(fn (Withdrawal $w): array => [
            'id' => $w->id,
            'user_name' => $w->user?->name,
            'user_email' => $w->user?->email,
            'amount' => $w->amount,
            'status' => $w->status,
            'audited_at' => $w->audited_at?->format('Y-m-d H:i:s'),
            'created_at' => $w->created_at?->format('Y-m-d H:i:s'),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $withdrawals->total(),
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
            ],
        ]);
    }

    /**
     * 提现审批通过（JSON API，供 React SPA Admin 后台使用）。
     */
    public function apiApprove(Request $request, int $id): JsonResponse
    {
        $withdrawal = $this->withdrawalService->approve($id, (int) $request->user()?->id);

        $this->auditService->log(
            (int) $request->user()?->id,
            'admin.withdrawal.approve',
            'withdrawal',
            $withdrawal->id,
            ['amount' => $withdrawal->amount],
        );

        return response()->json([
            'code' => 0,
            'msg' => "提现单 #{$withdrawal->id} 已核对通过",
            'data' => ['id' => $withdrawal->id, 'status' => $withdrawal->status],
        ]);
    }

    /**
     * 提现驳回（JSON API，供 React SPA Admin 后台使用）。
     */
    public function apiReject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:255'],
        ]);

        $withdrawal = $this->withdrawalService->reject(
            $id,
            $validated['reject_reason'],
            (int) $request->user()?->id,
        );

        $this->auditService->log(
            (int) $request->user()?->id,
            'admin.withdrawal.reject',
            'withdrawal',
            $withdrawal->id,
            ['reject_reason' => $validated['reject_reason']],
        );

        return response()->json([
            'code' => 0,
            'msg' => "提现单 #{$withdrawal->id} 已驳回，冻结款已退回可用余额",
            'data' => ['id' => $withdrawal->id, 'status' => $withdrawal->status],
        ]);
    }
}
