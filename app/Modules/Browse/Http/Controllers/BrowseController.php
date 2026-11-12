<?php

declare(strict_types=1);

namespace App\Modules\Browse\Http\Controllers;

use App\Modules\Browse\Models\Browse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 浏览历史
 */
final class BrowseController
{
    /**
     * 获取当前用户的浏览历史（关联商品信息）。
     */
    public function index(Request $request): JsonResponse
    {
        $list = Browse::query()
            ->with('product:id,name,cover,price')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $items = collect($list->items())->map(fn (Browse $browse): array => [
            'id' => $browse->id,
            'product_id' => $browse->product_id,
            'product_name' => $browse->product?->name,
            'product_cover' => $browse->product?->cover,
            'product_price' => $browse->product?->price,
            'created_at' => $browse->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $items,
                'total' => $list->total(),
                'current_page' => $list->currentPage(),
                'last_page' => $list->lastPage(),
            ],
        ]);
    }
}
