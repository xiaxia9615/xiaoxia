<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SkillCallHttpTest extends TestCase
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

    private function makeUserWithToken(string $email): array
    {
        $user  = $this->makeUser($email);
        $plain = 'sk_' . uniqid();
        ApiKey::create([
            'user_id'    => $user->id,
            'name'       => 'test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash'   => hash('sha256', $plain),
        ]);
        $token = $user->createToken('api')->plainTextToken;
        return [$user, $token];
    }

    private function makeSkill(int $price, int $providerId, array $detailOverrides = []): Product
    {
        $product = Product::create([
            'user_id'     => $providerId,
            'type'        => 1,
            'name'        => 'test skill',
            'slug'        => 'test-' . uniqid(),
            'description' => 'x',
            'price'       => $price,
            'status'      => 1,
        ]);
        SkillDetail::create(array_merge([
            'product_id'       => $product->id,
            'endpoint'         => 'https://api.example.com/skill',
            'method'           => 'POST',
            'provider_share'   => 70,
            'platform_share'   => 15,
            'influencer_share' => 10,
        ], $detailOverrides));
        return $product;
    }

    private function topUp(User $user, int $amount): void
    {
        app(LedgerService::class)->record('topup', $user->id, [
            [
                'owner_type'  => 'platform',
                'owner_id'    => 0,
                'account_type' => 'platform',
                'direction'   => 'debit',
                'amount'      => $amount,
            ],
            [
                'owner_type'  => 'user',
                'owner_id'    => $user->id,
                'account_type' => 'balance',
                'direction'   => 'credit',
                'amount'      => $amount,
            ],
        ]);
    }

    // ─── 场景 1：HTTP 调用成功 → 产生记录、正确扣款 ───

    public function test_http_call_success_creates_record_and_charges(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['result' => 'ok', 'data' => 'hello world'], 200),
        ]);

        [$caller, $token] = $this->makeUserWithToken('http1@test.com');
        $provider = $this->makeUser('phttp1@test.com');
        $skill    = $this->makeSkill(1000, $provider->id, [
            'api_key'          => 'sk-test-api-key',
            'request_template' => ['model' => 'gpt-4', 'temperature' => 0.7],
        ]);
        $this->topUp($caller, 5000);

        $res = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['prompt' => 'hello'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        // 断言：ApiCall 记录已创建
        $this->assertEquals(1, ApiCall::count());
        $call = ApiCall::first();
        $this->assertEquals(200, $call->status_code);
        $this->assertEquals(1000, $call->cost);
        $this->assertEquals(['result' => 'ok', 'data' => 'hello world'], $call->output);

        // 断言：余额正确扣款
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(4000, $balance);

        // 断言：返回数据正确
        $this->assertEquals($call->id, $res->json('data.call_id'));
        $this->assertEquals(['result' => 'ok', 'data' => 'hello world'], $res->json('data.result'));

        // 断言：请求体正确组装（template + input）
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/skill'
                && $request['model'] === 'gpt-4'
                && $request['temperature'] === 0.7
                && $request['prompt'] === 'hello';
        });
    }

    // ─── 场景 2：HTTP 调用失败（4xx）→ 不产生记录、不扣款 ───

    public function test_http_call_4xx_does_not_create_record_or_charge(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        [$caller, $token] = $this->makeUserWithToken('http2@test.com');
        $provider = $this->makeUser('phttp2@test.com');
        $skill    = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['prompt' => 'hello'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(400)
            ->assertJsonPath('code', 1);

        // 断言：不产生 ApiCall 记录
        $this->assertEquals(0, ApiCall::count());

        // 断言：余额不变
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(5000, $balance);
    }

    // ─── 场景 3：HTTP 调用失败（5xx）→ 不产生记录、不扣款 ───

    public function test_http_call_5xx_does_not_create_record_or_charge(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'server error'], 500),
        ]);

        [$caller, $token] = $this->makeUserWithToken('http3@test.com');
        $provider = $this->makeUser('phttp3@test.com');
        $skill    = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['prompt' => 'hello'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(400)
            ->assertJsonPath('code', 1);

        // 断言：不产生 ApiCall 记录
        $this->assertEquals(0, ApiCall::count());

        // 断言：余额不变
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(5000, $balance);
    }

    // ─── 场景 4：HTTP 调用超时 → 不产生记录、不扣款 ───

    public function test_http_call_timeout_does_not_create_record_or_charge(): void
    {
        Http::fake([
            'api.example.com/*' => function () {
                // 模拟连接超时
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Connection timed out');
            },
        ]);

        [$caller, $token] = $this->makeUserWithToken('http4@test.com');
        $provider = $this->makeUser('phttp4@test.com');
        $skill    = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['prompt' => 'hello'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(400)
            ->assertJsonPath('code', 1);

        // 断言：不产生 ApiCall 记录
        $this->assertEquals(0, ApiCall::count());

        // 断言：余额不变
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(5000, $balance);
    }

    // ─── 场景 5：无 api_key 时也能正常调用 ───

    public function test_http_call_without_api_key_succeeds(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['result' => 'ok'], 200),
        ]);

        [$caller, $token] = $this->makeUserWithToken('http5@test.com');
        $provider = $this->makeUser('phttp5@test.com');
        $skill    = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['prompt' => 'hello'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertEquals(1, ApiCall::count());

        // 断言：请求没有带 Authorization 头（因为 api_key 为空）
        Http::assertSent(function ($request) {
            return ! $request->hasHeader('Authorization');
        });
    }

    // ─── 场景 6：GET 方法也能正常调用 ───

    public function test_http_call_with_get_method_succeeds(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['result' => 'ok'], 200),
        ]);

        [$caller, $token] = $this->makeUserWithToken('http6@test.com');
        $provider = $this->makeUser('phttp6@test.com');
        $skill    = $this->makeSkill(1000, $provider->id, [
            'method' => 'GET',
        ]);
        $this->topUp($caller, 5000);

        $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => ['q' => 'search'],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertEquals(1, ApiCall::count());
    }
}
