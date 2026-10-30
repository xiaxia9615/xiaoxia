<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('生产环境: 仅执行基础数据 (ProductionSeeder)');
            $this->call(ProductionSeeder::class);
            $this->call(AiModelsSeeder::class);
            return;
        }

        $this->call([
            ProductionSeeder::class,
            DevelopmentSeeder::class,
        ]);

        $this->call(AiModelsSeeder::class);
    }
}
