<?php

declare(strict_types=1);

namespace Tests\Feature\RateLimit;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing 使用 array cache store，flush 一次清干净所有限流计数，避免用例间串配额
        Cache::flush();
    }

    private function makeUserWithToken(string $email): array
    {
        $user = User::create([
            'name' => 'ratelimit',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 1,
            'is_admin' => false,
        ]);
        $plain = 'sk_'.uniqid();
        ApiKey::create([
            'user_id' => $user->id,
            'name' => 'ratelimit',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash' => hash('sha256', $plain),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        return [$user, $token];
    }

    // ─── 场景 1：已登录用户未超限（60 次内）请求正常通过，不返回 429 ───
    public function test_gateway_under_limit_passes(): void
    {
        [, $token] = $this->makeUserWithToken('rl1@test.com');

        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson('/api/v1/skills/gateway', ['prompt' => ''], [
                'Authorization' => "Bearer {$token}",
            ]);
            $this->assertNotSame(429, $res->status());
        }

        $this->assertTrue(true);
    }

    // ─── 场景 2：同一用户超过 60 次/分钟，第 61 次返回 429 ───
    public function test_gateway_same_user_exceeds_60_returns_429(): void
    {
        [, $token] = $this->makeUserWithToken('rl2@test.com');

        for ($i = 0; $i < 60; $i++) {
            $res = $this->postJson('/api/v1/skills/gateway', ['prompt' => ''], [
                'Authorization' => "Bearer {$token}",
            ]);
            $this->assertNotSame(429, $res->status());
        }

        $res = $this->postJson('/api/v1/skills/gateway', ['prompt' => ''], [
            'Authorization' => "Bearer {$token}",
        ]);

        $res->assertStatus(429);
    }

    // ─── 场景 3：429 响应带 Retry-After 头 + JSON 三字段（code/message/retry_after）───
    public function test_429_response_has_retry_after_header_and_json_fields(): void
    {
        [, $token] = $this->makeUserWithToken('rl3@test.com');

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/skills/gateway', ['prompt' => ''], [
                'Authorization' => "Bearer {$token}",
            ]);
        }

        $res = $this->postJson('/api/v1/skills/gateway', ['prompt' => ''], [
            'Authorization' => "Bearer {$token}",
        ]);

        $res->assertStatus(429);
        $this->assertTrue($res->headers->has('Retry-After'));
        $retryAfter = (int) $res->headers->get('Retry-After');
        $this->assertGreaterThanOrEqual(1, $retryAfter);
        $res->assertJsonPath('code', 429);
        $res->assertJsonPath('message', __('messages.rate_limited'));
        $this->assertSame($retryAfter, (int) $res->json('retry_after'));
    }

    // ─── 场景 4：登录端点同 IP 超过 10 次/分钟，第 11 次返回 429 ───
    public function test_login_exceeds_10_per_ip_returns_429(): void
    {
        $payload = ['email' => 'nobody@test.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson('/api/v1/auth/login', $payload);
            $this->assertNotSame(429, $res->status());
        }

        $res = $this->postJson('/api/v1/auth/login', $payload);

        $res->assertStatus(429);
        $this->assertTrue($res->headers->has('Retry-After'));
        $res->assertJsonPath('code', 429);
    }
}
