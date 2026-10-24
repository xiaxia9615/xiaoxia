<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class RegisterThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing 使用 array cache store，flush 一次清干净所有限流计数，避免用例间串配额
        Cache::flush();
    }

    // ─── 场景：register 同 IP 超过 10 次/分钟，第 11 次返回 429 ───
    public function test_register_is_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson('/api/v1/auth/register', [
                'name' => "throttle{$i}",
                'email' => "throttle{$i}@test.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
            $this->assertNotSame(429, $res->status());
        }

        $res = $this->postJson('/api/v1/auth/register', [
            'name' => 'throttle10',
            'email' => 'throttle10@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(429);
        $this->assertTrue($res->headers->has('Retry-After'));
        $res->assertJsonPath('code', 429);
    }
}
