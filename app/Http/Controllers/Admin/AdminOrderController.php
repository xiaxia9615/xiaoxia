<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly LedgerContract $ledger,
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
     * 订单退款：仅已支付订单可退款。
     *
     * 事务内通过 LedgerContract 复式记账：
     * 1. 原路退回买家余额（买家 credit +amount）；
     * 2. 按 order_commission 实际分账流水反向扣回（开发者/达人/平台各自 debit 原分成额），
     *    record() 不校验余额，达人余额不足时允许透支为负数；
     * 3. biz_type 为 order_refund，退款前校验流水，防止重复退款。
     * 成功后订单状态置为 Refunded。
     */
    public function refund(Request $request, int $id)
    {
        $refunded = DB::transaction(function () use ($request, $id): Order {
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

            // 原路退回买家余额
            $splits = [
                [
                    'owner_type' => 'user',
                    'owner_id' => (int) $buyer->id,
                    'account_type' => 'balance',
                    'direction' => 'credit',
                    'amount' => $amount,
                ],
            ];

            // 反向回滚分账：按 order_commission 实际分账流水的 credit 分录原额反向扣回
            // （开发者 80%、达人 10%、平台 10%/20%）；record() 不校验余额，达人余额不足时允许透支为负
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
                    $splits[] = [
                        'owner_type' => $credit->owner_type,
                        'owner_id' => (int) $credit->owner_id,
                        'account_type' => $credit->account_type,
                        'direction' => 'debit',
                        'amount' => (int) $credit->amount,
                    ];
                }
            }

            $this->ledger->record('order_refund', $order->id, $splits);

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

        return back()->with('status', "订单 {$refunded->order_no} 已退款");
    }
}
