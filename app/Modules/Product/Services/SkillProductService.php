<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Str;

final class SkillProductService implements SkillProductContract
{
    public function listSkills(): array
    {
        return Product::where('type', 1)->where('status', 1)
            ->with('provider:id,name')->orderByDesc('id')
            ->paginate(20)->toArray();
    }

    public function findSkill(int $id): ?array
    {
        $product = Product::where('type', 1)->with('provider:id,name')->find($id);

        return $product?->toArray();
    }

    public function createSkillProduct(int $userId, string $name, string $description, int $price): int
    {
        $product = Product::create([
            'user_id' => $userId,
            'type' => 1,
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'description' => $description,
            'price' => $price,
            'status' => 0,
        ]);

        return (int) $product->id;
    }

    public function auditSkill(int $id, bool $approve, ?string $note): ?array
    {
        $product = Product::where('type', 1)->find($id);
        if (! $product) {
            return null;
        }
        $product->update([
            'status' => $approve ? 1 : 2,
            'audit_note' => $note,
        ]);

        return ['id' => (int) $product->id, 'status' => (int) $product->status];
    }
}
