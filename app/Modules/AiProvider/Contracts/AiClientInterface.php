<?php
declare(strict_types=1);
namespace App\Modules\AiProvider\Contracts;
use App\Modules\AiProvider\DTOs\AiResponseDTO;
interface AiClientInterface
{
    public function chat(string $prompt): AiResponseDTO;
}
