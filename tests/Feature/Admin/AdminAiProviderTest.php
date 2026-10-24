<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\AiProvider\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAiProviderTest extends TestCase
{
    use RefreshDatabase;

    private const FULL_KEY = 'sk-test-abcdef1234567890';

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeProvider(): AiProvider
    {
        return AiProvider::create([
            'name'              => 'test-provider',
            'display_name'      => '测试供应商',
            'base_url'          => 'https://api.example.com/v1',
            'driver'            => 'openai',
            'model_name'        => 'gpt-test',
            'api_key'           => self::FULL_KEY,
            'enabled'           => true,
            'cost_per_1k_input' => 1,
            'cost_per_1k_output' => 2,
        ]);
    }

    public function test_admin_can_view_providers(): void
    {
        $provider = $this->makeProvider();

        $this->actingAs($this->admin())
            ->get('/admin/ai-providers')
            ->assertOk()
            ->assertSee('test-provider');
    }

    public function test_api_key_is_masked(): void
    {
        $provider = $this->makeProvider();

        $response = $this->actingAs($this->admin())
            ->get('/admin/ai-providers');

        $response->assertOk();
        // 断言响应不含完整 key
        $response->assertDontSee(self::FULL_KEY);
        // 断言含脱敏形式（末 4 位）
        $response->assertSee('sk-****7890');
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/ai-providers')
            ->assertForbidden();
    }
}
