<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plain = 'sk_'.Str::random(32);

        return [
            'user_id' => User::factory(),
            'name' => fake()->word().' key',
            'key_hash' => hash('sha256', $plain),
            'key_prefix' => substr($plain, 0, 11),
            'status' => 1,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
