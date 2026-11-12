<?php

declare(strict_types=1);

namespace App\Modules\System\Providers;

use Illuminate\Support\ServiceProvider;

final class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // System 模块服务通过类型自动注入
    }

    public function boot(): void
    {
        //
    }
}
