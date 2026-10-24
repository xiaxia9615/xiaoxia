<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Clients;

use App\Modules\AiProvider\Contracts\AiClientInterface;
use App\Modules\AiProvider\DTOs\AiResponseDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI 兼容协议通用客户端（数据驱动）。
 *
 * 国内主流大模型（DeepSeek、Kimi、通义、豆包等）均兼容 OpenAI 格式，
 * 通过注入 base_url / model_name / 成本字段即可接入任意供应商，
 * 无需为每家供应商新建重复的 Client 类。
 */
final class OpenAiCompatibleClient implements AiClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $modelName,
        private readonly float $costPer1kInput,
        private readonly float $costPer1kOutput,
        private readonly ?string $apiKey = null,
    ) {}

    public function chat(string $prompt): AiResponseDTO
    {
        $endpoint = rtrim($this->baseUrl, '/').'/chat/completions';

        $request = Http::connectTimeout(5)->timeout(30);

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $request = $request->withToken($this->apiKey);
        }

        try {
            $response = $request->post($endpoint, [
                'model' => $this->modelName,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])->throw();
        } catch (ConnectionException $e) {
            // 网络超时 / 连接失败
            throw new RuntimeException(
                $this->modelName.' 请求失败：connection failed - '.$e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            // 非 2xx 响应（如 500、401）
            throw new RuntimeException(
                $this->modelName.' 请求失败：HTTP '.$e->response->status(),
                0,
                $e
            );
        }

        $data = $response->json();
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        $inputTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($usage['completion_tokens'] ?? 0);

        // 费用 = 输入 token 数 / 1000 * 输入单价 + 输出 token 数 / 1000 * 输出单价
        $cost = $inputTokens / 1000 * $this->costPer1kInput
            + $outputTokens / 1000 * $this->costPer1kOutput;

        return new AiResponseDTO(
            content: (string) ($data['choices'][0]['message']['content'] ?? ''),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: $cost,
        );
    }
}
