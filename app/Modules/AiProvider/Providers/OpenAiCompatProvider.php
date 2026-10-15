<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Providers;

use App\Modules\AiProvider\Contracts\ChatProviderInterface;
use App\Modules\AiProvider\Dto\ChatResult;
use App\Modules\AiProvider\Models\AiModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenAiCompatProvider implements ChatProviderInterface
{
    public function chat(AiModel $model, array $messages, array $options = []): ChatResult
    {
        $provider = $model->provider;

        $temperature = $options['temperature'] ?? 0.7;

        $payload = [
            'model'       => $model->name,
            'messages'    => $messages,
            'temperature' => $temperature,
        ];

        try {
            $response = Http::withToken($provider->api_key)
                ->timeout(30)
                ->post($provider->base_url . '/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'AI provider connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            $statusCode = $response->status();
            $body       = $response->body();
            $summary    = mb_substr($body, 0, 200);

            throw new RuntimeException(
                "AI provider returned HTTP {$statusCode}: {$summary}"
            );
        }

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? '';
        $promptTokens     = (int) ($data['usage']['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($data['usage']['completion_tokens'] ?? 0);

        return new ChatResult(
            content:          $content,
            promptTokens:     $promptTokens,
            completionTokens: $completionTokens,
            raw:              $data,
        );
    }
}
