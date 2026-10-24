<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * 基础数据: 任何环境都可安全执行.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DomesticAiProvidersSeeder::class,
        ]);
    }
}
