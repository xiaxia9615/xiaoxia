<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Providers;

use App\Modules\AiProvider\Clients\GlmClient;
use App\Modules\AiProvider\Contracts\AiClientInterface;
use App\Modules\AiProvider\Contracts\AiExecutionContract;
use App\Modules\AiProvider\Contracts\ChatProviderInterface;
use App\Modules\AiProvider\Services\AiExecutionService;
use Illuminate\Support\ServiceProvider;

final class AiProviderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ChatProviderInterface::class,
            OpenAiCompatProvider::class,
        );
        $this->app->bind(
            AiClientInterface::class,
            GlmClient::class,
        );
        $this->app->bind(
            AiExecutionContract::class,
            AiExecutionService::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
