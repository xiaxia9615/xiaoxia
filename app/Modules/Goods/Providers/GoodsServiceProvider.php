<?php

declare(strict_types=1);

namespace App\Modules\Goods\Providers;

use Illuminate\Support\ServiceProvider;

final class GoodsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Goods 模块无独立迁移，路由在 routes/api.php 中注册
    }
}
