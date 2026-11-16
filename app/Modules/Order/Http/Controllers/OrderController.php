<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class OrderController
{
    public function __construct(
        private readonly OrderService $service,
    ) {}

    /**
     * 创建订单（不扣款，状态为待支付）
     *
     * @group 订单管理
     *
     * @authenticated
     *
     * @bodyParam product_id int required 商品ID Example: 1
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->service->createOrder((int) $request->user()->id, (int) $data['product_id']);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['code' => 0, 'data' => $order]);
    }

    /**
     * 支付订单（模拟支付，幂等：已支付订单直接返回）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->payOrder($id, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'code' => 0,
            'data' => $result['order'],
            'message' => $result['message'] ?? '支付成功',
        ]);
    }

    /**
     * 取消订单（仅待支付订单可取消）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->service->cancelOrder($id, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['code' => 0, 'data' => $order]);
    }

    /**
     * 订单列表（分页）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)->paginate(10);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $orders]);
    }

    /**
     * 订单详情
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $this->toDetailPayload($order)]);
    }

    /**
     * 查询订单状态
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['status' => $order->status]]);
    }

    /** @group 订单管理 */
    public function orderIndex(Request $request): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    /** @group 订单管理 */
    public function orderDetail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $order = Order::where('user_id', $request->user()->id)->find((int) $data['id']);

        if ($order === null) {
            return response()->json(['code' => 1, 'msg' => '订单不存在'], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $this->toDetailPayload($order)]);
    }

    /** @group 订单管理 */
    public function orderCancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->service->cancelOrder((int) $data['order_id'], (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()], 400);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $this->toDetailPayload($order)]);
    }

    /** @group 订单管理 */
    public function orderDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|integer|min:1',
        ]);

        $order = Order::where('user_id', $request->user()->id)->find((int) $data['order_id']);

        if ($order === null) {
            return response()->json(['code' => 1, 'msg' => '订单不存在'], 404);
        }

        if (! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Completed], true)) {
            return response()->json(['code' => 1, 'msg' => '仅已取消或已完成的订单可删除'], 400);
        }

        $order->delete();

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function toDetailPayload(Order $order): array
    {
        $snapshot = $order->product_snapshot ?? [];

        return [
            'id' => $order->id,
            'order_sn' => $order->order_no,
            'order_status' => $this->statusToNumber($order->status),
            'status_name' => $order->status->label(),
            'pay_status' => $order->paid_at !== null ? 1 : 0,
            'goods_amount' => $order->amount,
            'total_amount' => $order->amount,
            'pay_amount' => $order->amount,
            'shipping_fee' => 0,
            'receiver_name' => $snapshot['receiver_name'] ?? '',
            'receiver_phone' => $snapshot['receiver_phone'] ?? '',
            'receiver_region' => $snapshot['receiver_region'] ?? '',
            'receiver_address' => $snapshot['receiver_address'] ?? '',
            'nature' => $snapshot['nature'] ?? 'physical',
            'tracking_no' => $order->tracking_no ?? '',
            'items' => [[
                'id' => $order->id,
                'goods_id' => $order->product_id,
                'name' => $order->product_name,
                'image' => $snapshot['cover'] ?? '',
                'price' => $order->amount,
                'quantity' => 1,
            ]],
            'add_time' => $order->created_at?->toDateTimeString(),
            'pay_time' => $order->paid_at?->toDateTimeString(),
        ];
    }

    private function statusToNumber(OrderStatus $status): int
    {
        return match ($status) {
            OrderStatus::Pending => 0,
            OrderStatus::Paid => 1,
            OrderStatus::PendingShipment => 2,
            OrderStatus::Completed => 3,
            OrderStatus::Cancelled => 4,
            OrderStatus::Refunded => 5,
        };
    }
}
