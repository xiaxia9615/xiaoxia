<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\System\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminOrderController extends Controller
{
    public function __construct(
    ) {}

    /**
     * 订单列表：分页展示全部订单，预加载买家与商品，按下单时间倒序
     */
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
            'express_company' => $order->express_company,
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
            'express_company' => 'required|string|max:32',
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
            $order->express_company = $data['express_company'];
            $order->shipped_at = now();
            $order->status = OrderStatus::Completed;
            $order->save();

            $auditService->log(
                (int) $request->user()->id,
                'admin.order.ship',
                'order',
                $order->id,
                ['order_no' => $order->order_no, 'tracking_no' => $order->tracking_no, 'express_company' => $order->express_company],
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
                'express_company' => $order->express_company,
                'shipped_at' => $order->shipped_at?->toDateTimeString(),
                'status' => $order->status->value,
            ],
        ]);
    }

    /**
     * 查询物流轨迹（JSON API）。
     *
     * 返回订单的物流单号、快递公司及轨迹信息。
     * 轨迹通过快递100免费接口查询（jsonp 格式，无需授权）。
     */
    public function apiTracking(int $id)
    {
        $order = Order::query()->find($id);
        if (! $order) {
            return response()->json(['code' => 1, 'msg' => '订单不存在'], 404);
        }
        if (! $order->tracking_no) {
            return response()->json(['code' => 1, 'msg' => '该订单尚未发货'], 400);
        }
        $companyMap = [
            'SF' => '顺丰速运', 'YTO' => '圆通速递', 'ZTO' => '中通快递',
            'YD' => '韵达快递', 'STO' => '申通快递', 'JD' => '京东物流',
            'EMS' => 'EMS', 'TT' => '天天快递', 'DBL' => '德邦快递',
            'HTKY' => '百世快递', 'JTSD' => '极兔速递',
        ];
        $companyCode = $order->express_company ?? 'SF';
        $companyName = $companyMap[$companyCode] ?? $companyCode;
        $tracks = [];
        try {
            $url = 'https://www.kuaidi100.com/query?type='.$companyCode.'&postid='.$order->tracking_no.'&temp='.random_int(100000, 999999);
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp) {
                $data = json_decode($resp, true);
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $item) {
                        $tracks[] = [
                            'time' => $item['time'] ?? '',
                            'context' => $item['context'] ?? '',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // 查询失败不阻断，返回空轨迹
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'order_no' => $order->order_no,
                'tracking_no' => $order->tracking_no,
                'express_company' => $companyCode,
                'express_company_name' => $companyName,
                'shipped_at' => $order->shipped_at?->toDateTimeString(),
                'tracks' => $tracks,
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
}
