<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Modules\Cart\Models\CartItem;
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
            'data' => $result,
            'message' => '支付二维码已生成',
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
     * 购物车/单品下单入口（前端 buy/add）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function buyAdd(Request $request): JsonResponse
    {
        $productId = (int) $request->input('product_id');
        if ($productId <= 0) {
            return response()->json(['code' => 422, 'msg' => '商品 ID 无效', 'data' => null], 422);
        }

        try {
            $order = $this->service->createOrder((int) $request->user()->id, $productId);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 422, 'msg' => $e->getMessage(), 'data' => null], 422);
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'order_ids' => [$order->id],
                'order_sn' => $order->order_no,
                'payment_id' => 0,
            ],
        ]);
    }

    /**
     * 结算预览（前端 buy/index）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function buyIndex(Request $request): JsonResponse
    {
        $cartItems = CartItem::query()
            ->with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        $items = [];
        $totalPrice = 0;
        $goodsCount = 0;

        foreach ($cartItems as $item) {
            $product = $item->product;
            if ($product === null) {
                continue;
            }
            $price = (int) $product->price;
            $quantity = (int) $item->quantity;
            $subtotal = $price * $quantity;
            $totalPrice += $subtotal;
            $goodsCount += $quantity;

            $items[] = [
                'id' => $item->id,
                'goods_id' => $item->product_id,
                'goods_name' => $product->name,
                'image' => $product->cover,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }

        $shippingAmount = 0;
        $actualPrice = $totalPrice + $shippingAmount;

        $payments = [
            ['id' => 1, 'name' => '支付宝', 'channel' => 'alipay'],
            ['id' => 2, 'name' => '微信支付', 'channel' => 'weixin'],
        ];

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'items' => $items,
                'total_price' => $totalPrice,
                'shipping_amount' => $shippingAmount,
                'actual_price' => $actualPrice,
                'goods_count' => $goodsCount,
                'address' => null,
                'payments' => $payments,
                'default_payment_id' => 1,
            ],
        ]);
    }

    /**
     * 发起支付（前端 pay/submit）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function paySubmit(Request $request): JsonResponse
    {
        $orderId = (int) ($request->input('orderId') ?: $request->input('order_id') ?: 0);
        if ($orderId <= 0) {
            return response()->json(['code' => 400, 'msg' => '订单 ID 无效', 'data' => null], 400);
        }

        try {
            $result = $this->service->payOrder($orderId, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 422, 'msg' => $e->getMessage(), 'data' => null], 422);
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => array_merge(['order_id' => $orderId], $result),
        ]);
    }

    /**
     * 查询支付状态（前端 pay/query）
     *
     * @group 订单管理
     *
     * @authenticated
     */
    public function payQuery(Request $request): JsonResponse
    {
        $orderId = (int) ($request->input('orderId') ?: $request->input('order_id') ?: 0);

        if ($orderId <= 0) {
            return response()->json([
                'code' => 0,
                'msg' => 'ok',
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'pending',
                ],
            ]);
        }

        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if ($order === null) {
            return response()->json([
                'code' => 0,
                'msg' => 'ok',
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'pending',
                ],
            ]);
        }

        // 主动查单兜底：同步订单状态后再返回
        $order = $this->service->syncOrderStatus($order->id, (int) $request->user()->id);

        $status = match ($order->status) {
            OrderStatus::Pending => 'pending',
            OrderStatus::Paid, OrderStatus::PendingShipment, OrderStatus::Completed => 'paid',
            OrderStatus::Cancelled => 'cancelled',
            OrderStatus::Refunded => 'refunded',
        };

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'order_id' => $orderId,
                'status' => $status,
                'order_status' => $order->status->value,
                'status_name' => $order->status->label(),
                'pay_status' => $order->paid_at !== null ? 1 : 0,
                'pay_time' => $order->paid_at?->toDateTimeString(),
            ],
        ]);
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

        // 主动查单兜底：同步订单状态后再返回
        $order = $this->service->syncOrderStatus($order->id, (int) $request->user()->id);

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
        $orders = Order::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $list = $orders->map(fn (Order $order): array => $this->toDetailPayload($order))->all();

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => $list, 'total' => count($list)]]);
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

        // 主动查单兜底：同步订单状态后再返回
        $order = $this->service->syncOrderStatus($order->id, (int) $request->user()->id);

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
