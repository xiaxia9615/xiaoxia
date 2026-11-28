<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdminProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create([
                'user_id' => auth()->id(),
                'type' => (int) $data['type'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.uniqid(),
                'description' => $data['description'] ?? null,
                'cover' => $data['cover'] ?? null,
                'price' => (int) $data['price'],
                'status' => (int) $data['status'],
                'nature' => $data['nature'] ?? Product::NATURE_PHYSICAL,
                'quota' => (int) ($data['quota'] ?? 0),
                'skill_id' => $data['skill_id'] ?? null,
            ]);
        });
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);
            $product->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'cover' => $data['cover'] ?? null,
                'price' => (int) $data['price'],
                'type' => (int) $data['type'],
                'nature' => $data['nature'] ?? $product->nature,
                'quota' => (int) ($data['quota'] ?? $product->quota),
                'skill_id' => $data['skill_id'] ?? $product->skill_id,
            ]);

            return $product;
        });
    }

    public function toggleStatus(int $id): Product
    {
        return DB::transaction(function () use ($id) {
            $product = Product::findOrFail($id);
            $product->update(['status' => $product->status === 1 ? 0 : 1]);

            return $product;
        });
    }
}
