<?php
declare(strict_types=1);
namespace App\Modules\AiProvider\DTOs;
final class AiResponseDTO
{
    public function __construct(
        public string $content,
        public int $inputTokens,
        public int $outputTokens,
        public float $cost,
    ) {}
}
