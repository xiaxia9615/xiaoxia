<?php

declare(strict_types=1);

namespace App\Modules\Aftersale\Http\Controllers;

use App\Modules\Aftersale\Services\AftersaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @group 售后管理
 */
final class AftersaleController
{
    public function __construct(
        private readonly AftersaleService $service,
    ) {}

    /**
     * 售后申请列表（分页，联查订单信息）.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->service->paginate((int) $request->user()->id, $perPage);

        $list = collect($items->items())->map(fn ($a): array => [
            'id' => $a->id,
            'order_id' => $a->order_id,
            'order_no' => $a->order_no,
            'type' => $a->type,
            'type_label' => match ($a->type) {
                1 => '退款',
                2 => '退货退款',
                3 => '换货',
                default => '未知',
            },
            'status' => $a->status,
            'status_label' => match ($a->status) {
                'pending' => '待处理',
                'approved' => '已通过',
                'rejected' => '已驳回',
                'completed' => '已完成',
                'cancelled' => '已取消',
                default => $a->status,
            },
            'reason' => $a->reason,
            'description' => $a->description,
            'refund_amount' => (float) $a->refund_amount,
            'admin_note' => $a->admin_note,
            'order_product_name' => $a->order?->product_name,
            'order_amount' => $a->order ? (float) $a->order->amount : null,
            'order_status' => $a->order?->status,
            'created_at' => $a->created_at?->toDateTimeString(),
            'processed_at' => $a->processed_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    /**
     * 创建售后申请.
     */
    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|integer|min:1',
            'type' => 'required|integer|in:1,2,3',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        try {
            $aftersale = $this->service->create((int) $request->user()->id, $data);

            return response()->json([
                'code' => 0,
                'msg' => 'ok',
                'data' => [
                    'id' => $aftersale->id,
                    'order_id' => $aftersale->order_id,
                    'status' => $aftersale->status,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'code' => 1,
                'msg' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 取消售后申请.
     */
    public function cancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        try {
            $ok = $this->service->cancel((int) $request->user()->id, (int) $data['id']);

            return response()->json([
                'code' => 0,
                'msg' => $ok ? '已取消' : '取消失败',
                'data' => ['id' => $data['id'], 'status' => 'cancelled'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'code' => 1,
                'msg' => $e->getMessage(),
            ], 400);
        }
    }
}
