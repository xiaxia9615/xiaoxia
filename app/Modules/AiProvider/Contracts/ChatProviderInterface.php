<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Contracts;

use App\Modules\AiProvider\Dto\ChatResult;
use App\Modules\AiProvider\Models\AiModel;

interface ChatProviderInterface
{
    public function chat(AiModel $model, array $messages, array $options = []): ChatResult;
}
