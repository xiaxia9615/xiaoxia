<?php

declare(strict_types=1);

namespace App\Modules\Article\Http\Controllers;

use App\Modules\Article\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 文章管理
 */
final class ArticleController
{
    /**
     * 文章列表（分页）。
     */
    public function index(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $list = collect($articles->items())->map(fn (Article $article): array => [
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'category' => $article->category,
            'created_at' => $article->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $articles->total(),
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
            ],
        ]);
    }

    /**
     * 文章详情。
     */
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        $article = Article::query()->find($id);

        if ($article === null) {
            return response()->json(['code' => 1, 'msg' => '文章不存在', 'data' => null], 404);
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'category' => $article->category,
                'created_at' => $article->created_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * 分类列表（去重）。
     */
    public function category(): JsonResponse
    {
        $categories = Article::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->values()
            ->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['list' => $categories],
        ]);
    }
}
