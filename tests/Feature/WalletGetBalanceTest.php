<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class WalletGetBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_balance_for_user_without_wallet_returns_zero_and_does_not_create_wallet(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/wallet');

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $response->assertJsonPath('data.balance', 0);
        $response->assertJsonPath('data.frozen_balance', 0);

        $this->assertSame(0, Wallet::count(), 'wallets table should remain empty after GET /api/v1/wallet for user without wallet');
    }
}
