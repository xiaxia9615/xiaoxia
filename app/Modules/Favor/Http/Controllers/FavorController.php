<?php

declare(strict_types=1);

namespace App\Modules\Favor\Http\Controllers;

use App\Modules\Favor\Services\FavorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 收藏管理
 */
final class FavorController
{
    public function __construct(
        private readonly FavorService $service,
    ) {}

    /**
     * 收藏列表（分页，联查商品信息）.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->service->paginate((int) $request->user()->id, $perPage);

        $list = collect($items->items())->map(fn ($f): array => [
            'id' => $f->id,
            'product_id' => $f->product_id,
            'product_name' => $f->product?->name,
            'product_cover' => $f->product?->cover,
            'product_price' => $f->product ? (float) $f->product->price : null,
            'product_status' => $f->product?->status,
            'product_type' => $f->product?->type,
            'created_at' => $f->created_at?->toDateTimeString(),
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
     * 检查是否已收藏.
     */
    public function status(Request $request): JsonResponse
    {
        $productId = (int) $request->input('product_id', 0);
        if ($productId <= 0) {
            return response()->json(['code' => 422, 'msg' => 'product_id required'], 422);
        }

        $isFavor = $this->service->isFavorited((int) $request->user()->id, $productId);

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['is_favor' => $isFavor],
        ]);
    }

    /**
     * 收藏商品.
     */
    public function save(Request $request): JsonResponse
    {
        $productId = (int) $request->input('product_id', 0);
        if ($productId <= 0) {
            return response()->json(['code' => 422, 'msg' => 'product_id required'], 422);
        }

        $created = $this->service->save((int) $request->user()->id, $productId);

        return response()->json([
            'code' => 0,
            'msg' => $created ? '已收藏' : '已在收藏中',
            'data' => ['product_id' => $productId, 'is_favor' => true],
        ]);
    }

    /**
     * 取消收藏.
     */
    public function delete(Request $request): JsonResponse
    {
        $productId = (int) $request->input('product_id', 0);
        if ($productId <= 0) {
            return response()->json(['code' => 422, 'msg' => 'product_id required'], 422);
        }

        $deleted = $this->service->delete((int) $request->user()->id, $productId);

        return response()->json([
            'code' => 0,
            'msg' => $deleted ? '已取消收藏' : '未收藏该商品',
            'data' => ['product_id' => $productId, 'is_favor' => false],
        ]);
    }
}
