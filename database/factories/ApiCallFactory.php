<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiCall>
 */
class ApiCallFactory extends Factory
{
    protected $model = ApiCall::class;

    public function definition(): array
    {
        $cost = fake()->numberBetween(100, 10000);
        $p = (int) ($cost * 0.70);
        $pl = (int) ($cost * 0.15);
        $inf = (int) ($cost * 0.10);
        $d = $cost - $p - $pl - $inf;

        return [
            'request_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'api_key_id' => ApiKey::factory(),
            'product_id' => Product::factory()->skill(),
            'provider_id' => User::factory(),
            'input' => ['prompt' => fake()->sentence()],
            'output' => ['result' => fake()->paragraph()],
            'status_code' => 200,
            'duration_ms' => fake()->numberBetween(50, 3000),
            'cost' => $cost,
            'provider_income' => $p,
            'platform_income' => $pl,
            'influencer_income' => $inf,
            'donation_amount' => $d,
        ];
    }
}
