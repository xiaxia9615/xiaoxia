<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Modules\Ledger\Contracts\Events\AiUsageRecorded;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Wallet\Listeners\DeductBalanceOnAiUsage;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

final class DeductBalanceOnAiUsageTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    private LedgerContract $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
        $this->ledger = app(LedgerContract::class);
    }

    private function createUserWithBalance(int $amount = 10000): User
    {
        $user = User::factory()->create();
        $this->walletService->deposit($user, $amount, 'test_deposit_'.uniqid());

        return $user;
    }

    public function test_deducts_balance_on_ai_usage(): void
    {
        $user = $this->createUserWithBalance(10000);

        $listener = app(DeductBalanceOnAiUsage::class);
        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 1,
            cost: 0.003,
            inputTokens: 100,
            outputTokens: 50,
        ));

        // 0.003 元 → 3 毫，余额应从 10000 减至 9997
        $balance = $this->walletService->getBalance($user);
        $this->assertSame(9997, $balance['balance']);
    }

    public function test_wallet_ledger_balance_consistency_after_deduction(): void
    {
        $user = $this->createUserWithBalance(10000);

        $listener = app(DeductBalanceOnAiUsage::class);
        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 2,
            cost: 1.5,
            inputTokens: 500,
            outputTokens: 200,
        ));

        // 1.5 元 → 1500 毫，余额应从 10000 减至 8500
        $walletBalance = $this->walletService->getBalance($user);
        $ledgerBalance = $this->ledger->balance('user', $user->id, 'balance');

        $this->assertSame(8500, $walletBalance['balance']);
        $this->assertSame($walletBalance['balance'], $ledgerBalance);
    }

    public function test_creates_ai_usage_deduction_record(): void
    {
        $user = $this->createUserWithBalance(10000);

        $listener = app(DeductBalanceOnAiUsage::class);
        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 3,
            cost: 0.5,
            inputTokens: 300,
            outputTokens: 100,
        ));

        $this->assertDatabaseHas('ai_usage_deductions', [
            'user_id' => $user->id,
            'provider_id' => 3,
            'input_tokens' => 300,
            'output_tokens' => 100,
        ]);

        // 验证 cost 字段精度
        $record = DB::table('ai_usage_deductions')->where('user_id', $user->id)->first();
        $this->assertEquals('0.500000', $record->cost);
    }

    public function test_throws_when_balance_insufficient(): void
    {
        $user = $this->createUserWithBalance(2); // 仅 2 毫

        $listener = app(DeductBalanceOnAiUsage::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/余额不足/');

        // 0.003 元 → 3 毫，余额仅 2 毫
        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 1,
            cost: 0.003,
            inputTokens: 10,
            outputTokens: 5,
        ));
    }

    public function test_throws_when_cost_is_zero(): void
    {
        $user = $this->createUserWithBalance(10000);

        $listener = app(DeductBalanceOnAiUsage::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/必须为正数/');

        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 1,
            cost: 0.0,
            inputTokens: 0,
            outputTokens: 0,
        ));
    }

    public function test_precision_conversion_ceil_to_millis(): void
    {
        $user = $this->createUserWithBalance(10000);

        $listener = app(DeductBalanceOnAiUsage::class);

        // 0.0035 元 → 3500 微毫 → ceil(3500/1000) = 4 毫
        $listener->handle(new AiUsageRecorded(
            userId: $user->id,
            providerId: 1,
            cost: 0.0035,
            inputTokens: 10,
            outputTokens: 5,
        ));

        $balance = $this->walletService->getBalance($user);
        $this->assertSame(9996, $balance['balance']);

        // ledger 对账
        $this->assertSame(
            $balance['balance'],
            $this->ledger->balance('user', $user->id, 'balance'),
        );
    }

    public function test_event_triggers_listener(): void
    {
        $user = $this->createUserWithBalance(10000);

        Event::fake();
        event(new AiUsageRecorded(
            userId: $user->id,
            providerId: 1,
            cost: 0.01,
            inputTokens: 10,
            outputTokens: 5,
        ));

        Event::assertListening(
            AiUsageRecorded::class,
            DeductBalanceOnAiUsage::class,
        );
    }

    public function test_multiple_deductions_accumulate_correctly(): void
    {
        $user = $this->createUserWithBalance(10000);
        $listener = app(DeductBalanceOnAiUsage::class);

        // 三次扣费：0.003 元(3 毫) + 0.5 元(500 毫) + 0.001 元(1 毫)
        $listener->handle(new AiUsageRecorded($user->id, 1, 0.003, 10, 5));
        $listener->handle(new AiUsageRecorded($user->id, 1, 0.5, 20, 10));
        $listener->handle(new AiUsageRecorded($user->id, 1, 0.001, 5, 3));

        $balance = $this->walletService->getBalance($user);
        // 10000 - 3 - 500 - 1 = 9496
        $this->assertSame(9496, $balance['balance']);

        // ledger 对账
        $this->assertSame(
            $balance['balance'],
            $this->ledger->balance('user', $user->id, 'balance'),
        );

        // 三条流水
        $this->assertDatabaseCount('ai_usage_deductions', 3);
    }
}
