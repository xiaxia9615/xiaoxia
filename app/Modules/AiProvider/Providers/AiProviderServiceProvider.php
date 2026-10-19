<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Providers;

use App\Modules\AiProvider\Clients\GlmClient;
use App\Modules\AiProvider\Contracts\AiClientInterface;
use App\Modules\AiProvider\Contracts\ChatProviderInterface;
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
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
