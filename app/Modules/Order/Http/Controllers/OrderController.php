<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

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
}
