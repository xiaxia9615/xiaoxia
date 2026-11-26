<?php

declare(strict_types=1);

namespace App\Modules\Favor\Services;

use App\Modules\Favor\Models\Favorite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FavorService
{
    /**
     * 用户收藏列表（分页，联查商品信息）.
     */
    public function paginate(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Favorite::query()
            ->with('product:id,name,cover,price,status,type')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 检查用户是否已收藏某商品.
     */
    public function isFavorited(int $userId, int $productId): bool
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * 收藏商品（幂等：已存在则跳过）.
     */
    public function save(int $userId, int $productId): bool
    {
        return Favorite::firstOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
            ['user_id' => $userId, 'product_id' => $productId],
        )->wasRecentlyCreated;
    }

    /**
     * 取消收藏.
     */
    public function delete(int $userId, int $productId): bool
    {
        return (bool) Favorite::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }
}
