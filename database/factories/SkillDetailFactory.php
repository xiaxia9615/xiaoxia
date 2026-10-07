<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillDetail>
 */
class SkillDetailFactory extends Factory
{
    protected $model = SkillDetail::class;

    public function definition(): array
    {
        return [
            'product_id'       => Product::factory()->skill(),
            'endpoint'         => '/api/v1/skill/' . fake()->slug(2),
            'method'           => fake()->randomElement(['GET', 'POST']),
            'pricing_type'     => fake()->randomElement([1, 2, 3]),
            'provider_share'   => 70,
            'platform_share'   => 15,
            'influencer_share' => 10,
            'donation_share'   => 5,
            'free_quota'       => fake()->numberBetween(0, 100),
            'config'           => ['timeout' => 30, 'retry' => 3],
        ];
    }
}
