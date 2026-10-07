<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 主用户
        $mainUser = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 其他用户
        User::factory()->count(5)->create();

        // 主用户的商品
        $products = Product::factory()
            ->count(6)
            ->create(['user_id' => $mainUser->id]);

        // 技能类型的商品 → 建 SkillDetail
        $products->where('type', 1)->each(function (Product $product) {
            SkillDetail::factory()->create(['product_id' => $product->id]);
        });

        // API Key
        $apiKey = ApiKey::factory()->create(['user_id' => $mainUser->id]);

        // 调用记录
        ApiCall::factory()
            ->count(10)
            ->create([
                'user_id'     => $mainUser->id,
                'api_key_id'  => $apiKey->id,
                'provider_id' => $mainUser->id,
            ]);

        $this->command->info('Seeded: ' . User::count() . ' users, '
            . Product::count() . ' products, '
            . SkillDetail::count() . ' skills, '
            . ApiKey::count() . ' api keys, '
            . ApiCall::count() . ' api calls.');
    }
}
