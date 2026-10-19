<?php
declare(strict_types=1);
namespace App\Modules\AiProvider\Clients;
use App\Modules\AiProvider\Contracts\AiClientInterface;
use App\Modules\AiProvider\DTOs\AiResponseDTO;
final class GlmClient implements AiClientInterface
{
    public function chat(string $prompt): AiResponseDTO
    {
        // GLM-5.3 模拟请求：返回假数据，后续可替换为真实 HTTP 调用
        return new AiResponseDTO(
            content: 'GLM-5.3 模拟响应',
            inputTokens: 100,
            outputTokens: 200,
            cost: 0.003,
        );
    }
}
