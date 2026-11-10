<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::first()?->id ?? 1;

        $products = [
            ['name' => '禅意手作茶杯', 'price' => 12900, 'type' => 2],
            ['name' => '原木香薰扩香', 'price' => 8900, 'type' => 2],
            ['name' => '手工编织收纳篮', 'price' => 6800, 'type' => 2],
            ['name' => '陶瓷釉下彩碗', 'price' => 4500, 'type' => 2],
            ['name' => '亚麻棉麻桌布', 'price' => 9900, 'type' => 2],
            ['name' => '黄铜烛台', 'price' => 15600, 'type' => 2],
            ['name' => '手作皮革笔记本', 'price' => 7800, 'type' => 2],
            ['name' => '天然椰棕地垫', 'price' => 18900, 'type' => 2],
            ['name' => '玻璃密封茶仓', 'price' => 5600, 'type' => 2],
            ['name' => '竹纤维毛巾套装', 'price' => 3900, 'type' => 2],
        ];

        foreach ($products as $item) {
            Product::create([
                'user_id' => $userId,
                'type' => $item['type'],
                'name' => $item['name'],
                'slug' => Str::slug($item['name']).'-'.Str::random(6),
                'description' => $item['name'].' —— 精选好物，品质之选。',
                'cover' => null,
                'price' => $item['price'],
                'status' => 1,
                'audit_note' => null,
            ]);
        }

        $this->command->info('Seeded '.count($products).' products with Chinese names.');
    }
}
