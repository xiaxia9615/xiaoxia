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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SkillCallIdempotentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'test',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 1,
        ]);
    }

    private function makeUserWithToken(string $email): array
    {
        $user = $this->makeUser($email);
        $plain = 'sk_'.uniqid();
        ApiKey::create([
            'user_id' => $user->id,
            'name' => 'test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash' => hash('sha256', $plain),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        return [$user, $token];
    }

    private function makeSkill(int $price, int $providerId): Product
    {
        $product = Product::create([
            'user_id' => $providerId,
            'type' => 1,
            'name' => 'test skill',
            'slug' => 'test-'.uniqid(),
            'description' => 'x',
            'price' => $price,
            'status' => 1,
        ]);
        SkillDetail::create([
            'product_id' => $product->id,
            'endpoint' => '/api/v1/skill/test',
            'method' => 'GET',
            'provider_share' => 70,
            'platform_share' => 15,
            'influencer_share' => 10,
        ]);

        return $product;
    }

    private function topUp(User $user, int $amount): void
    {
        app(LedgerService::class)->record('topup', $user->id, [
            [
                'owner_type' => 'platform',
                'owner_id' => 0,
                'account_type' => 'platform',
                'direction' => 'debit',
                'amount' => $amount,
            ],
            [
                'owner_type' => 'user',
                'owner_id' => $user->id,
                'account_type' => 'balance',
                'direction' => 'credit',
                'amount' => $amount,
            ],
        ]);
    }

    // ─── 场景 1：同一 request_id 调用两次 → 幂等 ───

    public function test_same_request_id_twice_produces_one_record_and_charges_once(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'], 200)]);

        [$caller, $token] = $this->makeUserWithToken('idem1@test.com');
        $provider = $this->makeUser('pidem1@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $requestId = 'req-'.uniqid();
        $payload = ['input' => 'hello', 'request_id' => $requestId];
        $headers = ['Authorization' => "Bearer {$token}"];

        // 第一次调用
        $res1 = $this->postJson("/api/v1/skills/{$skill->id}/call", $payload, $headers)
            ->assertOk()
            ->assertJsonPath('code', 0);

        $callId1 = $res1->json('data.call_id');
        $this->assertNotNull($callId1);

        // 第二次调用（相同 request_id）
        $res2 = $this->postJson("/api/v1/skills/{$skill->id}/call", $payload, $headers)
            ->assertOk()
            ->assertJsonPath('code', 0);

        $callId2 = $res2->json('data.call_id');

        // 断言：返回同一个 call_id
        $this->assertEquals($callId1, $callId2, '相同 request_id 应返回同一个 call_id');

        // 断言：api_calls 表只有 1 条记录
        $this->assertEquals(1, ApiCall::where('request_id', $requestId)->count());

        // 断言：余额只扣一次（5000 - 1000 = 4000）
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(4000, $balance, '余额应只扣一次');
    }

    // ─── 场景 2：不同 request_id 调用两次 → 各自独立 ───

    public function test_different_request_ids_produce_two_records_and_charge_twice(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'], 200)]);

        [$caller, $token] = $this->makeUserWithToken('idem2@test.com');
        $provider = $this->makeUser('pidem2@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $headers = ['Authorization' => "Bearer {$token}"];

        // 第一次调用
        $res1 = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => 'hello',
            'request_id' => 'req-a-'.uniqid(),
        ], $headers)->assertOk()->assertJsonPath('code', 0);

        $callId1 = $res1->json('data.call_id');

        // 第二次调用（不同 request_id）
        $res2 = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => 'world',
            'request_id' => 'req-b-'.uniqid(),
        ], $headers)->assertOk()->assertJsonPath('code', 0);

        $callId2 = $res2->json('data.call_id');

        // 断言：两个不同的 call_id
        $this->assertNotEquals($callId1, $callId2, '不同 request_id 应产生不同的 call_id');

        // 断言：api_calls 表有 2 条记录
        $this->assertEquals(2, ApiCall::count());

        // 断言：余额扣两次（5000 - 1000 - 1000 = 3000）
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(3000, $balance, '余额应扣两次');
    }

    // ─── 场景 3：不传 request_id → 每次生成新 UUID，正常扣款 ───

    public function test_missing_request_id_generates_new_uuid_and_charges_each_time(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'], 200)]);

        [$caller, $token] = $this->makeUserWithToken('idem3@test.com');
        $provider = $this->makeUser('pidem3@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $headers = ['Authorization' => "Bearer {$token}"];

        // 第一次调用（不传 request_id）
        $res1 = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => 'first',
        ], $headers)->assertOk()->assertJsonPath('code', 0);

        $callId1 = $res1->json('data.call_id');

        // 第二次调用（不传 request_id）
        $res2 = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => 'second',
        ], $headers)->assertOk()->assertJsonPath('code', 0);

        $callId2 = $res2->json('data.call_id');

        // 断言：两个不同的 call_id
        $this->assertNotEquals($callId1, $callId2, '不传 request_id 每次应产生不同的 call_id');

        // 断言：api_calls 表有 2 条记录
        $this->assertEquals(2, ApiCall::count());

        // 断言：余额扣两次
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(3000, $balance, '不传 request_id 每次都应扣款');

        // 断言：每条记录的 request_id 都是有效 UUID
        $calls = ApiCall::all();
        foreach ($calls as $call) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $call->request_id,
                '自动生成的 request_id 应为有效 UUID'
            );
        }
    }

    // ─── 场景 4：并发请求同一 request_id → 只产生 1 条记录 ───

    public function test_existing_request_id_returns_cached_result(): void
    {
        [$caller, $token] = $this->makeUserWithToken('idem4@test.com');
        $provider = $this->makeUser('pidem4@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $requestId = 'concurrent-'.uniqid();
        $payload = ['input' => 'concurrent', 'request_id' => $requestId];
        $headers = ['Authorization' => "Bearer {$token}"];

        // 模拟并发：在事务中先插入一条记录，再发起请求
        DB::beginTransaction();
        ApiCall::create([
            'request_id' => $requestId,
            'user_id' => $caller->id,
            'api_key_id' => 1,
            'product_id' => $skill->id,
            'provider_id' => $provider->id,
            'input' => ['text' => 'pre-existing'],
            'output' => ['message' => 'pre-existing'],
            'status_code' => 200,
            'duration_ms' => 10,
            'cost' => 1000,
            'provider_income' => 700,
            'platform_income' => 150,
            'influencer_income' => 100,
            'donation_amount' => 50,
        ]);
        DB::commit();

        // 发起请求（request_id 已存在）
        $res = $this->postJson("/api/v1/skills/{$skill->id}/call", $payload, $headers)
            ->assertOk()
            ->assertJsonPath('code', 0);

        // 断言：返回的是已存在记录的 call_id
        $existingCall = ApiCall::where('request_id', $requestId)->first();
        $this->assertEquals($existingCall->id, $res->json('data.call_id'));

        // 断言：api_calls 表仍然只有 1 条记录
        $this->assertEquals(1, ApiCall::where('request_id', $requestId)->count());

        // 断言：余额未被额外扣款（5000 - 1000 = 4000，但预插入记录未走 ledger，
        // 实际请求命中幂等返回已有记录也不扣款，所以余额仍为 5000）
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(5000, $balance, '命中幂等不应重复扣款');
    }

    // ─── 场景 5：先插入记录再调用接口 → 返回已有记录 ───

    public function test_pre_inserted_request_id_returns_existing_record(): void
    {
        [$caller, $token] = $this->makeUserWithToken('idem6@test.com');
        $provider = $this->makeUser('pidem6@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $requestId = 'pre-inserted-'.uniqid();

        // 先直接插入一条记录（模拟并发中先落库的请求）
        $existing = ApiCall::create([
            'request_id' => $requestId,
            'user_id' => $caller->id,
            'api_key_id' => 1,
            'product_id' => $skill->id,
            'provider_id' => $provider->id,
            'input' => ['text' => 'pre-existing'],
            'output' => ['message' => 'pre-existing'],
            'status_code' => 200,
            'duration_ms' => 10,
            'cost' => 1000,
            'provider_income' => 700,
            'platform_income' => 150,
            'influencer_income' => 100,
            'donation_amount' => 50,
        ]);

        // 再调用接口传相同 request_id
        $res = $this->postJson("/api/v1/skills/{$skill->id}/call", [
            'input' => 'hello',
            'request_id' => $requestId,
        ], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('code', 0);

        // 断言：返回已有记录的 call_id
        $this->assertEquals($existing->id, $res->json('data.call_id'));

        // 断言：api_calls 表只有 1 条记录
        $this->assertEquals(1, ApiCall::where('request_id', $requestId)->count());

        // 断言：余额未被额外扣款
        $balance = app(LedgerService::class)->balance('user', $caller->id, 'balance');
        $this->assertEquals(5000, $balance, '命中幂等不应重复扣款');
    }

    // ─── 场景 6：幂等返回的数据完整性 ───

    public function test_idempotent_response_contains_complete_billing_data(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'], 200)]);

        [$caller, $token] = $this->makeUserWithToken('idem5@test.com');
        $provider = $this->makeUser('pidem5@test.com');
        $skill = $this->makeSkill(1000, $provider->id);
        $this->topUp($caller, 5000);

        $requestId = 'complete-'.uniqid();
        $payload = ['input' => 'test', 'request_id' => $requestId];
        $headers = ['Authorization' => "Bearer {$token}"];

        // 第一次调用
        $res1 = $this->postJson("/api/v1/skills/{$skill->id}/call", $payload, $headers)
            ->assertOk();

        // 第二次调用（幂等命中）
        $res2 = $this->postJson("/api/v1/skills/{$skill->id}/call", $payload, $headers)
            ->assertOk()
            ->assertJsonPath('code', 0);

        // 断言：两次返回的 billing 数据完全一致
        $this->assertEquals(
            $res1->json('data.billing'),
            $res2->json('data.billing'),
            '幂等返回的 billing 数据应与首次一致'
        );

        // 断言：billing 包含所有必要字段
        $billing = $res2->json('data.billing');
        $this->assertArrayHasKey('cost', $billing);
        $this->assertArrayHasKey('provider', $billing);
        $this->assertArrayHasKey('platform', $billing);
        $this->assertArrayHasKey('influencer', $billing);
        $this->assertArrayHasKey('donation', $billing);
    }
}
