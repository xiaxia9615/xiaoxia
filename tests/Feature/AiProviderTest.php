<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\AiProvider\Dto\ChatResult;
use App\Modules\AiProvider\Models\AiModel;
use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\AiProvider\Services\ChatRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(array $overrides = []): AiProvider
    {
        return AiProvider::create(array_merge([
            'name'         => 'deepseek',
            'display_name' => 'DeepSeek',
            'base_url'     => 'https://api.deepseek.com/v1',
            'api_key'      => 'sk-test-key',
            'enabled'      => true,
        ], $overrides));
    }

    private function makeModel(AiProvider $provider, array $overrides = []): AiModel
    {
        return AiModel::create(array_merge([
            'provider_id'          => $provider->id,
            'name'                 => 'deepseek-chat',
            'display_name'         => 'DeepSeek Chat',
            'input_price_per_1k'   => 1,
            'output_price_per_1k'  => 2,
            'enabled'              => true,
        ], $overrides));
    }

    // ─── 场景 1：成功调用 → 正确 content + token 数 ───
    public function test_chat_success_returns_correct_content_and_tokens(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '你好，我是 DeepSeek']],
                ],
                'usage' => [
                    'prompt_tokens'     => 10,
                    'completion_tokens' => 20,
                ],
            ], 200),
        ]);

        $provider = $this->makeProvider();
        $model    = $this->makeModel($provider);

        $router   = new ChatRouter();
        $provider = $router->route($model);

        $result = $provider->chat($model, [
            ['role' => 'user', 'content' => '你好'],
        ]);

        $this->assertInstanceOf(ChatResult::class, $result);
        $this->assertSame('你好，我是 DeepSeek', $result->content);
        $this->assertSame(10, $result->promptTokens);
        $this->assertSame(20, $result->completionTokens);

        // 断言请求体正确
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.deepseek.com/v1/chat/completions'
                && $request['model'] === 'deepseek-chat'
                && $request['messages'][0]['content'] === '你好'
                && $request['temperature'] === 0.7;
        });
    }

    // ─── 场景 2：4xx 响应 → 抛 RuntimeException ───
    public function test_chat_4xx_throws_runtime_exception(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response(['error' => 'Invalid API key'], 401),
        ]);

        $provider = $this->makeProvider();
        $model    = $this->makeModel($provider);

        $router       = new ChatRouter();
        $chatProvider = $router->route($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401');

        $chatProvider->chat($model, [
            ['role' => 'user', 'content' => '你好'],
        ]);
    }

    // ─── 场景 3：5xx 响应 → 抛 RuntimeException ───
    public function test_chat_5xx_throws_runtime_exception(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response(['error' => 'Internal error'], 500),
        ]);

        $provider = $this->makeProvider();
        $model    = $this->makeModel($provider);

        $router       = new ChatRouter();
        $chatProvider = $router->route($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        $chatProvider->chat($model, [
            ['role' => 'user', 'content' => '你好'],
        ]);
    }

    // ─── 场景 4：超时 → 抛 RuntimeException ───
    public function test_chat_timeout_throws_runtime_exception(): void
    {
        Http::fake([
            'api.deepseek.com/*' => function () {
                throw new ConnectionException('cURL error 28: Connection timed out');
            },
        ]);

        $provider = $this->makeProvider();
        $model    = $this->makeModel($provider);

        $router       = new ChatRouter();
        $chatProvider = $router->route($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('connection failed');

        $chatProvider->chat($model, [
            ['role' => 'user', 'content' => '你好'],
        ]);
    }

    // ─── 场景 5：禁用 provider → 抛 RuntimeException ───
    public function test_disabled_provider_throws_runtime_exception(): void
    {
        $provider = $this->makeProvider(['enabled' => false]);
        $model    = $this->makeModel($provider);

        $router = new ChatRouter();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is disabled');

        $router->route($model);
    }

    // ─── 场景 6：禁用 model → 抛 RuntimeException ───
    public function test_disabled_model_throws_runtime_exception(): void
    {
        $provider = $this->makeProvider();
        $model    = $this->makeModel($provider, ['enabled' => false]);

        $router = new ChatRouter();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is disabled');

        $router->route($model);
    }
}
