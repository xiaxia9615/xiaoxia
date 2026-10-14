<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SkillCallBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'test', 'email' => $email,
            'password' => bcrypt('password123'), 'status' => 1,
        ]);
    }

    private function makeUserWithToken(string $email): array
    {
        $user = $this->makeUser($email);
        $plain = 'sk_'.uniqid();
        ApiKey::create([
            'user_id'    => $user->id,
            'name'       => 'test',
            'key_prefix' => substr($plain, 0, 8),
            'key_hash'   => hash('sha256', $plain),
        ]);
        $token = $user->createToken('api')->plainTextToken;
        return [$user, $token];
    }

    private function makeSkill(int $price, int $providerId): Product
    {
        $product = Product::create([
            'user_id' => $providerId, 'type' => 1,
            'name' => 'test skill', 'slug' => 'test-'.uniqid(),
            'description' => 'x', 'price' => $price, 'status' => 1,
        ]);
        SkillDetail::create([
            'product_id' => $product->id,
            'endpoint' => '/api/v1/skill/test', 'method' => 'GET',
            'provider_share' => 70, 'platform_share' => 15,
            'influencer_share' => 10,
        ]);
        return $product;
    }

    public function test_register_grants_signup_bonus(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'a', 'email' => 'a@test.com', 'password' => 'password123',
        ])->assertOk();
        $user = User::where('email', 'a@test.com')->first();
        $this->assertEquals(100000, app(LedgerService::class)->balance('user', $user->id, 'balance'));
    }

    public function test_call_rejected_when_balance_insufficient(): void
    {
        [$caller, $token] = $this->makeUserWithToken('c@test.com');
        $provider = $this->makeUser('p2@test.com');
        $skill = $this->makeSkill(1000, $provider->id);

        $this->postJson("/api/v1/skills/{$skill->id}/call", ['input' => 'hi'], [
            'Authorization' => "Bearer {$token}",
        ])->assertStatus(400)->assertJsonPath('code', 1);

        $this->assertEquals(0, app(LedgerService::class)->balance('user', $caller->id, 'balance'));
    }

    public function test_call_succeeds_when_balance_sufficient(): void
    {
        [$caller, $token] = $this->makeUserWithToken('b@test.com');
        $provider = $this->makeUser('p@test.com');
        $skill = $this->makeSkill(1000, $provider->id);

        app(LedgerService::class)->record('topup', $caller->id, [
            ['owner_type'=>'platform','owner_id'=>0,'account_type'=>'platform','direction'=>'debit','amount'=>5000],
            ['owner_type'=>'user','owner_id'=>$caller->id,'account_type'=>'balance','direction'=>'credit','amount'=>5000],
        ]);

        $this->postJson("/api/v1/skills/{$skill->id}/call", ['input' => 'hi'], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()->assertJsonPath('code', 0);

        $this->assertEquals(4000, app(LedgerService::class)->balance('user', $caller->id, 'balance'));
    }
}
