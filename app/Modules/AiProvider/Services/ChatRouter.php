<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Services;

use App\Modules\AiProvider\Contracts\ChatProviderInterface;
use App\Modules\AiProvider\Models\AiModel;
use App\Modules\AiProvider\Providers\OpenAiCompatProvider;
use RuntimeException;

final class ChatRouter
{
    public function route(AiModel $model): ChatProviderInterface
    {
        $provider = $model->provider;

        if (! $provider->enabled) {
            throw new RuntimeException(
                "AI provider [{$provider->name}] is disabled."
            );
        }

        if (! $model->enabled) {
            throw new RuntimeException(
                "AI model [{$model->name}] is disabled."
            );
        }

        return new OpenAiCompatProvider();
    }
}
