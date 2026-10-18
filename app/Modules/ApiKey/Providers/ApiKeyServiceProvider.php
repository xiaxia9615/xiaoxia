<?php
declare(strict_types=1);
namespace App\Modules\ApiKey\Providers;
use App\Modules\ApiKey\Contracts\ApiCallContract;
use App\Modules\ApiKey\Contracts\ApiKeyContract;
use App\Modules\ApiKey\Services\ApiCallService;
use App\Modules\ApiKey\Services\ApiKeyQueryService;
use Illuminate\Support\ServiceProvider;

final class ApiKeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ApiKeyContract::class,
            ApiKeyQueryService::class,
        );
        $this->app->bind(
            ApiCallContract::class,
            ApiCallService::class,
        );
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
