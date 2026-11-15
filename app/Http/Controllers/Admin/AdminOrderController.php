<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\System\Services\AuditService;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly AuditService $auditService,
    ) {}

    /**
     * 订单列表：分页展示全部订单，预加载买家与商品，按下单时间倒序
     */
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['user', 'product'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', ['orders' => $orders]);
    }

    /**
     * API 订单列表：返回 JSON 分页数据，供前端后台使用。
     */
    public function apiIndex(Request $request)
    {
        $orders = Order::query()
            ->with(['user:id,name,email', 'product:id,name,cover,price,nature'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $list = $orders->getCollection()->map(fn ($order): array => [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'status' => $order->status->value,
            'nature' => $order->product?->nature,
            'tracking_no' => $order->tracking_no,
            'product_name' => $order->product?->name,
            'user_name' => $order->user?->name,
            'user_email' => $order->user?->email,
            'created_at' => $order->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
            ],
        ]);
    }

    /**
     * 实物订单发货（JSON API，供 React SPA Admin 后台使用）。
     *
     * 1. 仅待发货（pending_shipment）订单可发货；
     * 2. 幂等：已发货订单（tracking_no 已存在）直接返回成功，防止重复发货；
     * 3. 发货后记录物流单号与发货时间，订单状态置为已完成。
     */
    public function apiShip(Request $request, AuditService $auditService, int $id)
    {
        $data = $request->validate([
            'tracking_no' => 'required|string|max:64',
        ]);

        $order = DB::transaction(function () use ($data, $id, $auditService, $request): Order {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException('订单不存在');
            }

            // 幂等：已发货订单直接返回，严禁重复发货
            if ($order->status === OrderStatus::Completed && $order->tracking_no !== null) {
                return $order;
            }

            if ($order->status !== OrderStatus::PendingShipment) {
                throw new RuntimeException('当前订单状态不可发货');
            }

            $order->tracking_no = $data['tracking_no'];
            $order->shipped_at = now();
            $order->status = OrderStatus::Completed;
            $order->save();

            $auditService->log(
                (int) $request->user()->id,
                'admin.order.ship',
                'order',
                $order->id,
                ['order_no' => $order->order_no, 'tracking_no' => $order->tracking_no],
            );

            return $order;
        });

        return response()->json([
            'code' => 0,
            'msg' => "订单 {$order->order_no} 已发货",
            'data' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'tracking_no' => $order->tracking_no,
                'shipped_at' => $order->shipped_at?->toDateTimeString(),
                'status' => $order->status->value,
            ],
        ]);
    }

    /**
     * 订单退款：仅已支付订单可退款。
     *
     * 事务内通过 WalletService 复式记账（同步 wallets.balance 与 Ledger）：
     * 1. 原路退回买家余额（买家 credit +amount）；
     * 2. 按 order_commission 实际分账流水反向扣回（开发者/平台各自 debit 原分成额）；
     * 3. 达人 10% 佣金单独走 order_refund_commission 回滚，退款前校验余额，不足则拒绝；
     * 4. biz_type 为 order_refund，退款前校验流水，防止重复退款。
     * 成功后订单状态置为 Refunded。
     */
    public function refund(Request $request, int $id)
    {
        $refunded = DB::transaction(function () use ($id): Order {
            /** @var Order|null $order */
            $order = Order::query()
                ->with('user')
                ->lockForUpdate()
                ->find($id);

            if (! $order) {
                throw new RuntimeException('订单不存在');
            }

            if (! $order->status->canRefund()) {
                throw new RuntimeException('仅已支付订单可退款');
            }

            $buyer = $order->user;
            if (! $buyer) {
                throw new RuntimeException('买家不存在');
            }

            // 幂等：已存在 order_refund 流水则拒绝，防止重复退款
            $alreadyRefunded = DB::table('ledger_entries')
                ->where('biz_type', 'order_refund')
                ->where('biz_id', $order->id)
                ->exists();
            if ($alreadyRefunded) {
                throw new RuntimeException('订单已退款，请勿重复操作');
            }

            $amount = (int) $order->amount;

            // 达人佣金余额检查：不足则拒绝退款，防止负余额
            $distributorId = (int) DistributorRelation::where('user_id', $order->user_id)
                ->value('distributor_id');

            if ($distributorId > 0) {
                $distributorShare = intdiv($amount * 10, 100);
                $distributorBalance = (int) Wallet::where('user_id', $distributorId)->value('balance');

                if ($distributorBalance < $distributorShare) {
                    throw new RuntimeException(
                        "达人佣金已提现，退款将导致负余额（余额 {$distributorBalance}，需扣 {$distributorShare}），请人工处理。"
                    );
                }
            }

            // 分账回滚 splits（买家退款单独走 WalletService，不在此处）
            $splits = [];

            // 反向回滚分账：按 order_commission 实际分账流水的 credit 分录原额反向扣回
            // （开发者 80%、平台 10%/20%）；达人 10% 单独走 order_refund_commission
            $commissionCredits = DB::table('ledger_entries as e')
                ->join('ledger_accounts as a', 'e.account_id', '=', 'a.id')
                ->where('e.biz_type', 'order_commission')
                ->where('e.biz_id', $order->id)
                ->where('e.direction', 'credit')
                ->get(['a.owner_type', 'a.owner_id', 'a.account_type', 'e.amount']);

            if ($commissionCredits->isEmpty()) {
                // 无分账流水的订单：全额从平台账户扣回
                $splits[] = [
                    'owner_type' => 'platform',
                    'owner_id' => 0,
                    'account_type' => 'platform',
                    'direction' => 'debit',
                    'amount' => $amount,
                ];
            } else {
                foreach ($commissionCredits as $credit) {
                    // 达人 10% 单独走 order_refund_commission，此处跳过
                    if ($distributorId > 0
                        && $credit->owner_type === 'user'
                        && (int) $credit->owner_id === $distributorId
                    ) {
                        continue;
                    }

                    $splits[] = [
                        'owner_type' => $credit->owner_type,
                        'owner_id' => (int) $credit->owner_id,
                        'account_type' => $credit->account_type,
                        'direction' => 'debit',
                        'amount' => (int) $credit->amount,
                    ];
                }
            }

            $this->wallet->record('order_refund', $order->id, $splits);

            // 达人 10% 佣金回滚（独立 biz_type，便于审计与幂等）
            if ($distributorId > 0) {
                $distributorShare = intdiv($amount * 10, 100);
                $this->wallet->record('order_refund_commission', $order->id, [
                    [
                        'owner_type' => 'user',
                        'owner_id' => $distributorId,
                        'account_type' => 'balance',
                        'direction' => 'debit',
                        'amount' => $distributorShare,
                    ],
                    [
                        'owner_type' => 'platform',
                        'owner_id' => 0,
                        'account_type' => 'platform',
                        'direction' => 'credit',
                        'amount' => $distributorShare,
                    ],
                ]);
            }

            // 买家退款走 WalletService：同事务内同步 wallets.balance 与 Ledger，
            // 避免 refund 只写 Ledger 造成 wallet 缓存漂移。
            $this->wallet->deposit($buyer, $amount, 'refund_'.$order->id);

            $order->status = OrderStatus::Refunded;
            $order->save();

            return $order;
        });

        Log::info('admin.order_refunded', [
            'operator' => $request->user()?->id,
            'order_id' => $refunded->id,
            'order_no' => $refunded->order_no,
            'amount' => $refunded->amount,
            'ip' => $request->ip(),
        ]);

        $this->auditService->log(
            (int) $request->user()->id,
            'admin.order_refunded',
            'order',
            $refunded->id,
            ['order_no' => $refunded->order_no, 'amount' => $refunded->amount],
        );

        return back()->with('status', "订单 {$refunded->order_no} 已退款");
    }
}
