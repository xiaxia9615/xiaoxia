<?php

declare(strict_types=1);

namespace App\Modules\Product\Providers;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Product\Services\SkillProductService;
use Illuminate\Support\ServiceProvider;

final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SkillProductContract::class,
            SkillProductService::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
