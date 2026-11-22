<?php

declare(strict_types=1);

namespace App\Modules\Goods\Http\Controllers;

use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 商品管理
 */
final class GoodsController
{
    /**
     * 商品列表（分页，仅上架商品）
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->onSale()
            ->orderByDesc('id')
            ->paginate(10);

        $list = $products->getCollection()->map(fn (Product $p): array => $this->toDto($p))->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $products->total(),
                'page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * 商品分类（按 type 字段分组：1=技能，2=普通商品）
     */
    public function category(): JsonResponse
    {
        $categories = [
            ['id' => 1, 'name' => '技能服务'],
            ['id' => 2, 'name' => '普通商品'],
        ];

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $categories]);
    }

    /**
     * 商品详情
     */
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['code' => 422, 'msg' => '商品 ID 无效', 'data' => null], 422);
        }

        $product = Product::query()->onSale()->find($id);
        if ($product === null) {
            return response()->json(['code' => 404, 'msg' => '商品不存在或已下架', 'data' => null], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $this->toDto($product)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toDto(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'cover' => $p->cover,
            'price' => $p->price,
            'description' => $p->description,
            'type' => $p->type,
            'nature' => $p->nature,
            'sales_count' => $p->sales_count,
            'view_count' => $p->view_count,
        ];
    }
}
