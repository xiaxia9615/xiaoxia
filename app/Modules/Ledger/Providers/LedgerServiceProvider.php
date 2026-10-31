<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Providers;

use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Ledger\Listeners\CreateLedgerOnUserRegistered;
use App\Modules\Ledger\Listeners\DeductBalanceOnAiUsage;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\User\Contracts\Events\UserRegistered;
use Illuminate\Support\Facades\Event;
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

        Event::listen(
            UserRegistered::class,
            CreateLedgerOnUserRegistered::class,
        );
        Event::listen(
            AiUsageRecorded::class,
            DeductBalanceOnAiUsage::class,
        );
    }
}
