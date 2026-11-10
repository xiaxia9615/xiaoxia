<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
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
        $goodsNames = [
            '禅意手作茶杯', '原木香薰扩香', '手工编织收纳篮', '陶瓷釉下彩碗',
            '亚麻棉麻桌布', '黄铜烛台', '手作皮革笔记本', '天然椰棕地垫',
            '玻璃密封茶仓', '竹纤维毛巾套装', '粗陶花器', '实木砧板',
        ];
        $skillNames = [
            'AI 绘画创作', '文案润色优化', '数据分析报告', 'Logo 设计',
            '短视频脚本撰写', 'PPT 美化排版', '简历优化', '商业计划书撰写',
        ];
        $name = $type === 1
            ? fake()->randomElement($skillNames)
            : fake()->randomElement($goodsNames);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->paragraph(),
            'cover' => null,
            'price' => fake()->numberBetween(1900, 99900),
            'status' => 1,
            'audit_note' => null,
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
