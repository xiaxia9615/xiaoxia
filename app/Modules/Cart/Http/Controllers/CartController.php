<?php

declare(strict_types=1);

namespace App\Modules\Cart\Http\Controllers;

use App\Modules\Cart\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group 购物车管理
 */
final class CartController
{
    /**
     * 购物车列表：with 预加载商品关联，避免 N+1。
     */
    public function index(Request $request): JsonResponse
    {
        $list = CartItem::query()
            ->with('product')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (CartItem $item): array => $this->toDto($item))
            ->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['list' => $list, 'total' => count($list)],
        ]);
    }

    /**
     * 加入购物车 / 修改数量。
     *
     * - 带 id：更新该购物车项数量。
     * - 带 goods_id：updateOrCreate 原子操作，已有则累加数量，没有则新建。
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'goods_id' => [
                Rule::prohibitedIf($request->filled('id')),
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $user = $request->user();
        $quantity = (int) $validated['quantity'];

        if (! empty($validated['id'])) {
            $item = CartItem::query()
                ->where('user_id', $user->id)
                ->where('id', (int) $validated['id'])
                ->firstOrFail();

            $item->quantity = $quantity;
            $item->save();

            return response()->json([
                'code' => 0,
                'msg' => 'ok',
                'data' => $this->toDto($item->load('product')),
            ]);
        }

        $item = CartItem::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => (int) $validated['goods_id'],
            ],
            ['quantity' => $quantity]
        );

        // 已存在则原子累加数量
        if (! $item->wasRecentlyCreated) {
            $item->increment('quantity', $quantity);
        }

        $item = CartItem::query()->with('product')->find($item->id);

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $this->toDto($item),
        ]);
    }

    /**
     * 删除购物车项。
     */
    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $deleted = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->where('id', (int) $validated['id'])
            ->delete();

        if ($deleted === 0) {
            return response()->json(['code' => 1, 'msg' => '购物车项不存在', 'data' => []], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toDto(CartItem $item): array
    {
        $product = $item->product;

        return [
            'id' => $item->id,
            'goods_id' => $item->product_id,
            'goods_name' => $product?->name,
            'image' => $product?->cover,
            'price' => $product?->price,
            'quantity' => $item->quantity,
            'stock' => 99,
        ];
    }
}
