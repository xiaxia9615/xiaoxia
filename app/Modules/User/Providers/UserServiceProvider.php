<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use Illuminate\Support\ServiceProvider;

final class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
