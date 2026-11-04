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
     * 事务内通过 LedgerContract 复式记账将金额原路退回买家余额
     * （买家 credit +amount，平台 debit -amount，账目保持平衡），
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

            // 原路退回：买家余额 credit，平台账户 debit（与支付时的借贷方向相反，账目平衡）
            $this->ledger->record('order_refund', $order->id, [
                [
                    'owner_type' => 'user',
                    'owner_id' => (int) $buyer->id,
                    'account_type' => 'balance',
                    'direction' => 'credit',
                    'amount' => (int) $order->amount,
                ],
                [
                    'owner_type' => 'platform',
                    'owner_id' => 0,
                    'account_type' => 'platform',
                    'direction' => 'debit',
                    'amount' => (int) $order->amount,
                ],
            ]);

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
