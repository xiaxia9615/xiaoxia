<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('跳过: 生产环境不执行测试数据 seeder');
            return;
        }

        $mainUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        User::factory()->count(5)->create();

        $products = collect();
        for ($i = 0; $i < 3; $i++) {
            $products->push(Product::factory()->skill()->create(['user_id' => $mainUser->id]));
        }
        for ($i = 0; $i < 3; $i++) {
            $products->push(Product::factory()->goods()->create(['user_id' => $mainUser->id]));
        }
        $products->where('type', 1)->each(function (Product $product) {
            SkillDetail::factory()->create(['product_id' => $product->id]);
        });

        $apiKey = ApiKey::factory()->create(['user_id' => $mainUser->id]);
        $skillProduct = $products->where('type', 1)->first();
        ApiCall::factory()->count(10)->create([
            'user_id' => $mainUser->id,
            'api_key_id' => $apiKey->id,
            'product_id' => $skillProduct->id,
            'provider_id' => $mainUser->id,
        ]);

        $this->command->info(sprintf(
            'Seeded: %d users, %d products, %d skills, %d api keys, %d api calls.',
            User::count(), Product::count(), SkillDetail::count(),
            ApiKey::count(), ApiCall::count(),
        ));
    }
}
