<?php
declare(strict_types=1);
namespace Tests\Feature\Skill;

use App\Models\User;
use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\Ledger\Services\LedgerService;
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
}
