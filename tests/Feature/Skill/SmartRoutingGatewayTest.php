<?php
declare(strict_types=1);
namespace Tests\Feature\Skill;

use App\Models\User;
use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    /**
     * 充值并返回充值后的余额（记账单位：毫，1 元 = 1000 毫）
     */
    private function topUp(LedgerService $ledger, int $userId, int $millis): int
    {
        $ledger->record('topup', $userId, [
            ['owner_type' => 'platform', 'owner_id' => 0,          'account_type' => 'platform', 'direction' => 'debit',  'amount' => $millis],
            ['owner_type' => 'user',     'owner_id' => $userId,    'account_type' => 'balance', 'direction' => 'credit', 'amount' => $millis],
        ]);
        return $ledger->balance('user', $userId, 'balance');
    }

    public function test_gateway_routes_to_lowest_cost_provider_and_deducts_balance(): void
    {
        $ledger = app(LedgerService::class);
        $user = $this->makeUser('gateway@test.com');
        $token = $user->createToken('api')->plainTextToken;

        // 成本 0.01 的高价 Provider
        $expensive = $this->makeProvider([
            'name'               => 'expensive-provider',
            'display_name'       => 'Expensive Provider',
            'cost_per_1k_input'  => 0.005,
            'cost_per_1k_output' => 0.005,
        ]);
        // 成本 0.003 的低价 Provider（模拟 GLM-5.3）
        $glm = $this->makeProvider([
            'name'               => 'glm',
            'display_name'       => 'GLM-5.3',
            'cost_per_1k_input'  => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        // 清空熔断状态，拦截真实网络请求：GLM 返回 100 输入 + 200 输出 token（费用 0.003 元）
        Cache::flush();
        Http::fake([
            'open.bigmodel.cn/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'GLM-5.3 模拟响应']],
                ],
                'usage' => [
                    'prompt_tokens'     => 100,
                    'completion_tokens' => 200,
                ],
            ], 200),
        ]);

        // 充值 100 元（100000 毫），并记录调用前的真实初始余额
        $initialBalance = $this->topUp($ledger, $user->id, 100000);
        $this->assertSame(100000, $initialBalance);

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

        // 核心断言：真实余额确实被扣减。
        // 精度换算：0.003 元 = 3 毫（账本记账单位为毫，1 元 = 1000 毫），整数精确表示，无浮点误差。
        $finalBalance = $ledger->balance('user', $user->id, 'balance');
        $this->assertSame(
            $initialBalance - 3,
            $finalBalance,
            "真实余额应从 {$initialBalance} 毫扣减 3 毫（0.003 元）至 ".($initialBalance - 3).' 毫，实际为 '.$finalBalance.' 毫'
        );
        // 账本中应存在本次 AI 用量的 debit 分录（biz_type = ai_usage）
        $this->assertSame(1, DB::table('ledger_entries')->where('biz_type', 'ai_usage')->where('direction', 'debit')->count());
    }

    public function test_gateway_rejects_when_balance_insufficient_and_writes_nothing(): void
    {
        $ledger = app(LedgerService::class);
        $user = $this->makeUser('gateway-poor@test.com');
        $token = $user->createToken('api')->plainTextToken;

        $this->makeProvider([
            'name'               => 'glm',
            'display_name'       => 'GLM-5.3',
            'cost_per_1k_input'  => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        // 清空熔断状态，拦截真实网络请求（余额校验发生在 AI 调用之后，必须拦截）
        Cache::flush();
        Http::fake([
            'open.bigmodel.cn/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'GLM-5.3 模拟响应']],
                ],
                'usage' => [
                    'prompt_tokens'     => 100,
                    'completion_tokens' => 200,
                ],
            ], 200),
        ]);

        // 不充值：初始余额为 0 毫
        $initialBalance = $ledger->balance('user', $user->id, 'balance');
        $this->assertSame(0, $initialBalance);

        $response = $this->postJson('/api/v1/skills/gateway', [
            'prompt' => '你好，请介绍一下自己',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        // 余额不足：接口拒绝，余额不被扣成负数
        $response->assertStatus(400)->assertJsonPath('code', 1);
        $this->assertSame(
            $initialBalance,
            $ledger->balance('user', $user->id, 'balance'),
            '余额不足时余额必须保持不变，绝不允许扣成负数'
        );
        // 事务回滚：不产生扣费流水，也不产生账本分录（无脏数据）
        $this->assertSame(0, DB::table('ai_usage_deductions')->count());
        $this->assertSame(0, DB::table('ledger_entries')->where('biz_type', 'ai_usage')->count());
    }

    // ─── 场景 3：连续 3 次失败后熔断，第 4 次调用不再发请求，直接报错 ───
    public function test_gateway_circuits_provider_after_three_consecutive_failures(): void
    {
        $ledger = app(LedgerService::class);
        $user = $this->makeUser('gateway-circuit@test.com');
        $token = $user->createToken('api')->plainTextToken;

        $this->makeProvider([
            'name'               => 'glm',
            'display_name'       => 'GLM-5.3',
            'cost_per_1k_input'  => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        $this->topUp($ledger, $user->id, 100000);

        // 清空熔断状态，拦截真实网络请求：GLM 接口持续 500
        Cache::flush();
        Http::fake([
            'open.bigmodel.cn/*' => Http::response(['error' => 'Internal error'], 500),
        ]);

        // 连续 3 次调用均失败（HTTP 500）
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/skills/gateway', [
                'prompt' => '你好，请介绍一下自己',
            ], [
                'Authorization' => "Bearer {$token}",
            ])->assertStatus(400)->assertJsonPath('code', 1);
        }

        // 第 4 次调用：该 Provider 已被熔断（踢出路由池），不再发请求，直接报错
        $response = $this->postJson('/api/v1/skills/gateway', [
            'prompt' => '你好，请介绍一下自己',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 1)
            ->assertJsonPath('message', '所有 AI 服务暂不可用，请稍后再试');

        // 核心断言：仅发出 3 次 HTTP 请求，第 4 次调用被熔断器拦截，未发起任何请求
        Http::assertSentCount(3);
    }

    // ─── 场景 4：最便宜的 Provider 熔断后，自动切换到第二便宜的 Provider 并成功 ───
    public function test_gateway_fails_over_to_second_provider_after_circuit_break(): void
    {
        $ledger = app(LedgerService::class);
        $user = $this->makeUser('gateway-failover@test.com');
        $token = $user->createToken('api')->plainTextToken;

        // 成本 0.01 的高价 Provider（备用）
        $expensive = $this->makeProvider([
            'name'               => 'expensive-provider',
            'display_name'       => 'Expensive Provider',
            'cost_per_1k_input'  => 0.005,
            'cost_per_1k_output' => 0.005,
        ]);
        // 成本 0.003 的低价 Provider（首选）
        $this->makeProvider([
            'name'               => 'glm',
            'display_name'       => 'GLM-5.3',
            'cost_per_1k_input'  => 0.001,
            'cost_per_1k_output' => 0.002,
        ]);

        $this->topUp($ledger, $user->id, 100000);

        // 清空熔断状态；前 3 次请求 500（打向低价 Provider），第 4 次请求 200（切换到高价 Provider 后成功）
        Cache::flush();
        Http::fake([
            'open.bigmodel.cn/*' => Http::sequence()
                ->push(['error' => 'Internal error'], 500)
                ->push(['error' => 'Internal error'], 500)
                ->push(['error' => 'Internal error'], 500)
                ->push([
                    'choices' => [
                        ['message' => ['content' => '备用 Provider 响应']],
                    ],
                    'usage' => [
                        'prompt_tokens'     => 100,
                        'completion_tokens' => 200,
                    ],
                ], 200),
        ]);

        // 前 3 次调用：首选低价 Provider 连续失败
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/skills/gateway', [
                'prompt' => '你好，请介绍一下自己',
            ], [
                'Authorization' => "Bearer {$token}",
            ])->assertStatus(400)->assertJsonPath('code', 1);
        }

        // 第 4 次调用：低价 Provider 已熔断，自动切换到高价 Provider 并调用成功
        $response = $this->postJson('/api/v1/skills/gateway', [
            'prompt' => '你好，请介绍一下自己',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.content', '备用 Provider 响应')
            ->assertJsonPath('data.provider.id', $expensive->id)
            ->assertJsonPath('data.provider.name', 'expensive-provider');

        // 扣费流水应记在备用 Provider 名下
        $deduction = DB::table('ai_usage_deductions')->first();
        $this->assertNotNull($deduction);
        $this->assertSame($expensive->id, (int) $deduction->provider_id);
    }
}
