<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Modules\Product\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController
{
    public function __construct(
        private readonly CategoryService $service,
    ) {}

    public function tree(): JsonResponse
    {
        $categories = $this->service->listAll();
        $tree = $this->buildTree($categories, 0);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $tree]);
    }

    public function index(): JsonResponse
    {
        $categories = $this->service->paginate(20);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => [
            'list' => $categories->items(),
            'total' => $categories->total(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'per_page' => $categories->perPage(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:categories,slug',
            'parent_id' => 'sometimes|integer|min:0',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer|in:0,1',
        ]);
        $cat = $this->service->create($data);

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $cat], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'slug' => 'sometimes|string|max:50|unique:categories,slug,'.$id,
            'parent_id' => 'sometimes|integer|min:0',
            'sort' => 'sometimes|integer',
            'status' => 'sometimes|integer|in:0,1',
        ]);
        $cat = $this->service->update($id, $data);
        if ($cat === null) {
            return response()->json(['code' => 1, 'msg' => 'not found'], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => $cat]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        if (! $result) {
            return response()->json(['code' => 1, 'msg' => 'not found or has children'], 422);
        }

        return response()->json(['code' => 0, 'msg' => 'ok']);
    }

    private function buildTree($items, int $parentId): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ((int) $item->parent_id === $parentId) {
                $node = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'parent_id' => (int) $item->parent_id,
                    'sort' => (int) $item->sort,
                    'status' => (int) $item->status,
                    'created_at' => $item->created_at?->toDateTimeString(),
                    'children' => $this->buildTree($items, (int) $item->id),
                ];
                $tree[] = $node;
            }
        }

        return $tree;
    }
}
