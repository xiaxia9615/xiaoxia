<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $type = fake()->randomElement([1, 2]);
        $name = $type === 1
            ? fake()->words(2, true) . '技能'
            : fake()->words(2, true) . '商品';

        return [
            'user_id'     => \App\Models\User::factory(),
            'type'        => $type,
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'description' => fake()->paragraph(),
            'cover'       => null,
            'price'       => fake()->numberBetween(100, 100000),
            'status'      => 1,
            'audit_note'  => null,
        ];
    }

    public function skill(): static
    {
        return $this->state(fn () => ['type' => 1]);
    }

    public function goods(): static
    {
        return $this->state(fn () => ['type' => 2]);
    }

    public function onSale(): static
    {
        return $this->state(fn () => ['status' => 1]);
    }
}
