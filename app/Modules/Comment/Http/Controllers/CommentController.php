<?php

declare(strict_types=1);

namespace App\Modules\Comment\Http\Controllers;

use App\Modules\Comment\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 评论管理
 */
final class CommentController
{
    /**
     * 获取某商品的评论列表（关联用户信息）。
     */
    public function index(Request $request): JsonResponse
    {
        $productId = (int) $request->query('product_id', 0);

        $comments = Comment::query()
            ->with('user:id,name')
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $items = collect($comments->items())->map(fn (Comment $comment): array => [
            'id' => $comment->id,
            'product_id' => $comment->product_id,
            'content' => $comment->content,
            'user_name' => $comment->user?->name,
            'created_at' => $comment->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $items,
                'total' => $comments->total(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }
}
