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
 * 智谱 GLM 真实 HTTP 客户端。
 *
 * 调用 open.bigmodel.cn 的 chat/completions 接口，
 * 超时或非 2xx 响应统一抛出 RuntimeException，由上层记录熔断。
 */
final class GlmClient implements AiClientInterface
{
    /** 智谱开放平台 chat/completions 接口地址 */
    private const ENDPOINT = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';

    /** 使用的模型标识 */
    private const MODEL = 'glm-4';

    /** 每千 token 输入单价（元） */
    private const COST_PER_1K_INPUT = 0.01;

    /** 每千 token 输出单价（元） */
    private const COST_PER_1K_OUTPUT = 0.01;

    public function chat(string $prompt): AiResponseDTO
    {
        try {
            $response = Http::connectTimeout(5)->timeout(30)->post(self::ENDPOINT, [
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])->throw();
        } catch (ConnectionException $e) {
            // 网络超时 / 连接失败
            throw new RuntimeException('GLM 请求失败：connection failed - '.$e->getMessage(), 0, $e);
        } catch (RequestException $e) {
            // 非 2xx 响应（如 500、401）
            throw new RuntimeException(
                'GLM 请求失败：HTTP '.$e->response->status(),
                0,
                $e
            );
        }

        $data = $response->json();
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        $inputTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($usage['completion_tokens'] ?? 0);

        // 费用 = 输入 token 数 / 1000 * 输入单价 + 输出 token 数 / 1000 * 输出单价
        $cost = $inputTokens / 1000 * self::COST_PER_1K_INPUT
            + $outputTokens / 1000 * self::COST_PER_1K_OUTPUT;

        return new AiResponseDTO(
            content: (string) ($data['choices'][0]['message']['content'] ?? ''),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: $cost,
        );
    }
}
