<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
{
    public function listAll(): Collection
    {
        return Category::query()->orderBy('sort')->orderBy('id')->get();
    }

    public function paginate(int $perPage = 20)
    {
        return Category::query()->orderBy('sort')->orderBy('id')->paginate($perPage);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $cat = Category::query()->where('id', $id)->first();
        if ($cat === null) {
            return null;
        }
        $cat->update($data);

        return $cat;
    }

    public function delete(int $id): bool
    {
        $cat = Category::query()->where('id', $id)->first();
        if ($cat === null) {
            return false;
        }
        if (Category::query()->where('parent_id', $id)->exists()) {
            return false;
        }
        $cat->delete();

        return true;
    }

    public function activeCategories(): Collection
    {
        return Category::query()->where('status', 1)->orderBy('sort')->get(['id', 'name', 'slug', 'parent_id']);
    }
}
