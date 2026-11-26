<?php

declare(strict_types=1);

namespace App\Modules\Aftersale\Providers;

use Illuminate\Support\ServiceProvider;

final class AftersaleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
