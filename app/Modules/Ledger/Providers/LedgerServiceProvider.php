<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Providers;

use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Support\ServiceProvider;

final class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LedgerContract::class, LedgerService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
