<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Providers;

use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Wallet 模块服务通过类型自动注入（WalletService 无接口依赖）
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
