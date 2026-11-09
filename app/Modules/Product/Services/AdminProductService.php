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
                'price' => (int) $data['price'],
                'status' => (int) $data['status'],
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
                'price' => (int) $data['price'],
                'type' => (int) $data['type'],
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
