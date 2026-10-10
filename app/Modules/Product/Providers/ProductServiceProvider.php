<?php

declare(strict_types=1);

namespace App\Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;

final class ProductServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
