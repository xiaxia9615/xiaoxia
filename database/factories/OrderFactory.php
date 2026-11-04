<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_no' => $this->faker->unique()->numerify('############'),
            'user_id' => 1,
            'product_id' => 1,
            'amount' => 10000,
            'product_name' => 'Test Product',
            'product_snapshot' => [],
            'status' => 'pending',
        ];
    }
}
