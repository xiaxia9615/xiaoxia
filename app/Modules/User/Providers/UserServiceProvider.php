<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Modules\User\Contracts\UserContract;
use App\Modules\User\Services\UserService;
use Illuminate\Support\ServiceProvider;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserContract::class,
            UserService::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
