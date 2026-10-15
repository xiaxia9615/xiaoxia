<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Dto;

final class ChatResult
{
    public function __construct(
        public readonly string $content,
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly array $raw,
    ) {}
}
