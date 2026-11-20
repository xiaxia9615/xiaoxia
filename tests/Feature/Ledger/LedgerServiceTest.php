<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Models\User;
use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
    }

    private function userWithBalance(int $amount): User
    {
        $user = User::factory()->create();
        $this->ledger->record('test_credit', 1, [
            ['owner_type' => 'user', 'owner_id' => $user->id, 'account_type' => 'balance', 'direction' => 'credit', 'amount' => $amount],
            ['owner_type' => 'external', 'owner_id' => 0, 'account_type' => 'external', 'direction' => 'debit', 'amount' => $amount],
        ]);

        return $user;
    }

    public function test_debit_success_writes_two_entries_and_keeps_balanced(): void
    {
        $user = $this->userWithBalance(1000);
        $txId = $this->ledger->debit('user', $user->id, 'balance', 300, 'test_debit', 1);

        $this->assertNotEmpty($txId);
        $this->assertSame(700, $this->ledger->balance('user', $user->id, 'balance'));
        $this->assertSame(300, $this->ledger->balance('platform', 0, 'platform'));
        $this->assertSame(2, DB::table('ledger_entries')->where('transaction_id', $txId)->count());
    }

    public function test_debit_insufficient_balance_rolls_back(): void
    {
        $user = $this->userWithBalance(100);
        $before = DB::table('ledger_entries')->count();

        try {
            $this->ledger->debit('user', $user->id, 'balance', 200, 'test_debit', 1);
            $this->fail('expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('余额不足', $e->getMessage());
        }

        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(100, $this->ledger->balance('user', $user->id, 'balance'));
    }

    public function test_debit_zero_amount_throws(): void
    {
        $user = $this->userWithBalance(1000);
        $this->expectException(\RuntimeException::class);
        $this->ledger->debit('user', $user->id, 'balance', 0, 'test', 1);
    }

    public function test_debit_negative_amount_throws(): void
    {
        $user = $this->userWithBalance(1000);
        $this->expectException(\RuntimeException::class);
        $this->ledger->debit('user', $user->id, 'balance', -1, 'test', 1);
    }

    public function test_debit_account_not_found_throws(): void
    {
        $user = User::factory()->create();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('账户不存在');
        $this->ledger->debit('user', $user->id, 'balance', 100, 'test', 1);
    }

    public function test_multiple_debits_accumulate(): void
    {
        $user = $this->userWithBalance(1000);
        $this->ledger->debit('user', $user->id, 'balance', 100, 'test', 1);
        $this->ledger->debit('user', $user->id, 'balance', 200, 'test', 2);
        $this->ledger->debit('user', $user->id, 'balance', 300, 'test', 3);

        $this->assertSame(400, $this->ledger->balance('user', $user->id, 'balance'));
        $this->assertSame(600, $this->ledger->balance('platform', 0, 'platform'));
    }

    public function test_platform_account_created_once(): void
    {
        $user = $this->userWithBalance(1000);
        $this->ledger->debit('user', $user->id, 'balance', 10, 'test', 1);
        $this->ledger->debit('user', $user->id, 'balance', 10, 'test', 2);
        $this->ledger->debit('user', $user->id, 'balance', 10, 'test', 3);

        $count = DB::table('ledger_accounts')
            ->where('owner_type', 'platform')
            ->where('owner_id', 0)
            ->where('account_type', 'platform')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_balance_matches_sum_after_debit(): void
    {
        $user = $this->userWithBalance(500);
        $this->ledger->debit('user', $user->id, 'balance', 200, 'test', 1);

        $accountId = DB::table('ledger_accounts')
            ->where('owner_type', 'user')
            ->where('owner_id', $user->id)
            ->where('account_type', 'balance')
            ->value('id');

        $credit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'credit')->sum('amount');
        $debit = (int) DB::table('ledger_entries')->where('account_id', $accountId)->where('direction', 'debit')->sum('amount');

        $this->assertSame($credit - $debit, $this->ledger->balance('user', $user->id, 'balance'));
    }
}
