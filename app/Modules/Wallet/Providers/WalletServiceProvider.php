<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Providers;

use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use App\Modules\User\Contracts\Events\UserRegistered;
use App\Modules\Wallet\Contracts\WalletContract;
use App\Modules\Wallet\Listeners\CreateLedgerOnUserRegistered;
use App\Modules\Wallet\Listeners\DeductBalanceOnAiUsage;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Wallet 模块服务通过类型自动注入（WalletService 无接口依赖）
        $this->app->bind(
            WalletContract::class,
            WalletService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        Event::listen(
            AiUsageRecorded::class,
            DeductBalanceOnAiUsage::class,
        );

        Event::listen(
            UserRegistered::class,
            CreateLedgerOnUserRegistered::class,
        );
    }
}
