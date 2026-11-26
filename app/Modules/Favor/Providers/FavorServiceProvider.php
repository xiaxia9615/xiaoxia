<?php

declare(strict_types=1);

namespace App\Modules\Favor\Providers;

use Illuminate\Support\ServiceProvider;

final class FavorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
