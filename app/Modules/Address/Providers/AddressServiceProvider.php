<?php

declare(strict_types=1);

namespace App\Modules\Address\Providers;

use Illuminate\Support\ServiceProvider;

final class AddressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Address 模块无接口绑定需求
    }

    public function boot(): void
    {
        // 路由与迁移已由 routes/api.php 和 database/migrations 统一加载
    }
}
