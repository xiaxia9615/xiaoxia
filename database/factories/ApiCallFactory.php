<?php

declare(strict_types=1);

namespace Database\Factories;

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
        $providerIncome   = (int) ($cost * 0.70);
        $platformIncome   = (int) ($cost * 0.15);
        $influencerIncome = (int) ($cost * 0.10);
        $donationAmount   = $cost - $providerIncome - $platformIncome - $influencerIncome;

        return [
            'request_id'        => (string) Str::uuid(),
            'user_id'           => \App\Models\User::factory(),
            'api_key_id'        => ApiKey::factory(),
            'product_id'        => Product::factory()->skill(),
            'provider_id'       => \App\Models\User::factory(),
            'input'             => ['prompt' => fake()->sentence()],
            'output'            => ['result' => fake()->paragraph()],
            'status_code'       => fake()->randomElement([200, 200, 200, 400, 500]),
            'duration_ms'       => fake()->numberBetween(50, 3000),
            'cost'              => $cost,
            'provider_income'   => $providerIncome,
            'platform_income'   => $platformIncome,
            'influencer_income' => $influencerIncome,
            'donation_amount'   => $donationAmount,
        ];
    }
}
