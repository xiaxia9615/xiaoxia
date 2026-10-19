<?php
declare(strict_types=1);
namespace Tests\Feature\Skill;
use App\Models\User;
use App\Modules\AiProvider\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
final class SmartRoutingGatewayTest extends TestCase
{
    use RefreshDatabase;
    private function makeUser(string $email): User
    {
        return User::create([
            'name'     => 'test',
            'email'    => $email,
            'password' => bcrypt('password123'),
            'status'   => 1,
        ]);
    }
    private function makeProvider(array $overrides = []): AiProvider
    {
        return AiProvider::create(array_merge([
            'name'         => 'provider-'.uniqid(),
            'display_name' => 'Provider',
            'base_url'     => 'https://api.example.com/v1',
            'api_key'      => 'sk-test-key',
            'enabled'      => true,
        ], $overrides));
    }
    public function test_gateway_routes_to_lowest_cost_provider_and_deducts_balance(): void
    {
        $user = $this->makeUser('gateway@test.com');
        $token = $user->createToken('api')->plainTextToken;
        // 成本 0.01 的高价 Provider
        $expensive = $this->makeProvider([
            'name'              => 'expensive-provider',
            'display_name'      => 'Expensive Provider',
            'cost_per_1k_input' => 0.005,
            'cost_per_1k_output' => 0.005,
        ]);
        // 成本 0.003 的低价 Provider（模拟 GLM-5.3）
        $glm = $this->makeProvider([
            'name'              => 'glm',
            'display_name'      => 'GLM-5.3',
            'cost_per_1k_input' => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);
        $response = $this->postJson('/api/v1/skills/gateway', [
            'prompt' => '你好，请介绍一下自己',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.content', 'GLM-5.3 模拟响应')
            ->assertJsonPath('data.provider.id', $glm->id)
            ->assertJsonPath('data.provider.name', 'glm');
        // 断言扣费流水表中产生一条 0.003 的扣费记录
        $this->assertSame(1, DB::table('ai_usage_deductions')->count());
        $deduction = DB::table('ai_usage_deductions')->first();
        $this->assertNotNull($deduction);
        $this->assertSame($user->id, (int) $deduction->user_id);
        $this->assertSame($glm->id, (int) $deduction->provider_id);
        $this->assertEqualsWithDelta(0.003, (float) $deduction->cost, 0.000001);
        $this->assertSame(100, (int) $deduction->input_tokens);
        $this->assertSame(200, (int) $deduction->output_tokens);
        // 确认高价 Provider 未被选中
        $this->assertNotSame($expensive->id, (int) $deduction->provider_id);
    }
}
