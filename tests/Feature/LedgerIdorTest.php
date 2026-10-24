<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LedgerIdorTest extends TestCase
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

    private function credit(LedgerService $ledger, int $userId, int $amount, int $bizId): void
    {
        $ledger->record('idor_test', $bizId, [
            [
                'owner_type' => 'user',
                'owner_id' => $userId,
                'account_type' => 'balance',
                'direction' => 'credit',
                'amount' => $amount,
            ],
        ]);
    }

    public function test_user_cannot_read_others_balance(): void
    {
        $alice = $this->makeUser('alice-idor@test.com');
        $bob = $this->makeUser('bob-idor@test.com');

        $ledger = app(LedgerService::class);
        $this->credit($ledger, $alice->id, 150000, 1);
        $this->credit($ledger, $bob->id, 1000, 2);

        // Bob 只能看到自己的余额（1000），而不是 Alice 的（150000）
        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/ledger/balance')
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.balance', 1000);
    }

    public function test_user_cannot_read_others_entries(): void
    {
        $alice = $this->makeUser('alice-idor@test.com');
        $bob = $this->makeUser('bob-idor@test.com');

        $ledger = app(LedgerService::class);
        $this->credit($ledger, $alice->id, 150000, 1);
        $this->credit($ledger, $bob->id, 1000, 2);

        $bobAccountId = DB::table('ledger_accounts')
            ->where('owner_type', 'user')
            ->where('owner_id', $bob->id)
            ->where('account_type', 'balance')
            ->value('id');

        $response = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/ledger/entries')
            ->assertOk()
            ->assertJsonPath('code', 0);

        $entries = $response->json('data.data');
        $this->assertNotEmpty($entries);
        $this->assertCount(1, $entries);
        foreach ($entries as $entry) {
            // 只能看到自己账户的流水，绝不包含 Alice 的账户流水
            $this->assertSame($bobAccountId, $entry['account_id']);
        }
    }

    public function test_entries_returns_empty_for_user_without_account(): void
    {
        $alice = $this->makeUser('alice-idor@test.com');
        $bob = $this->makeUser('bob-idor@test.com');

        // 只有 Alice 有账户，Bob 没有
        $this->credit(app(LedgerService::class), $alice->id, 150000, 1);

        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/ledger/entries')
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', 0);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/ledger/balance')->assertUnauthorized();
        $this->getJson('/api/v1/ledger/entries')->assertUnauthorized();
    }
}
