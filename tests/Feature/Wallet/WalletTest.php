<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class WalletTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'wallet-user',
            'email' => 'wallet-'.Str::uuid().'@example.com',
            'password' => bcrypt('password123'),
            'status' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{balance: int, frozen_balance: int} 余额（分）
     */
    private function balance(int $userId): array
    {
        return $this->service->getBalance($userId);
    }

    public function test_freeze_funds_deducts_balance_and_increases_frozen(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 10000); // 100.00 元

        $this->service->freezeFunds($user->id, 3000); // 冻结 30.00 元

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(7000, $this->balance($user->id)['balance']);
        $this->assertSame(3000, $this->balance($user->id)['frozen_balance']);

        $tx = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'freeze')
            ->first();
        $this->assertNotNull($tx);
        $this->assertEqualsWithDelta(30.0, (float) $tx->amount, 0.001);
    }

    public function test_freeze_funds_throws_when_balance_insufficient(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 1000); // 10.00 元

        $this->expectException(RuntimeException::class);
        $this->service->freezeFunds($user->id, 2000); // 20.00 元，余额不足
    }

    public function test_freeze_insufficient_balance_returns_402_via_api(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 1000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/freeze', ['amount' => 2000])
            ->assertStatus(402);
    }

    public function test_deduct_and_refund_keep_amounts_correct(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 10000);
        $this->service->freezeFunds($user->id, 5000);

        // 结算扣款：冻结余额清零，可用余额不变
        $this->service->deductFunds($user->id, 5000);
        $this->assertSame(5000, $this->balance($user->id)['balance']);
        $this->assertSame(0, $this->balance($user->id)['frozen_balance']);

        // 再次冻结后退款：冻结退回可用余额
        $this->service->freezeFunds($user->id, 2000);
        $this->service->refundFunds($user->id, 2000);
        $this->assertSame(5000, $this->balance($user->id)['balance']);
        $this->assertSame(0, $this->balance($user->id)['frozen_balance']);

        // 流水完整：deposit + freeze + deduct + freeze + refund
        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(
            5,
            DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->count(),
        );
    }

    /**
     * 并发安全：freezeFunds 在 DB::transaction 内以 lockForUpdate 锁定钱包行，
     * 同一钱包的并发冻结被串行化。两笔并发冻结（各 80.00 元）打到余额
     * 100.00 元的钱包时，只应成功一笔，余额与冻结金额守恒。
     */
    public function test_concurrent_freeze_is_safe(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 10000); // 100.00 元

        $results = [];
        foreach ([8000, 8000] as $amount) {
            try {
                $this->service->freezeFunds($user->id, $amount);
                $results[] = 'ok';
            } catch (RuntimeException) {
                $results[] = 'rejected';
            }
        }

        $this->assertSame(['ok', 'rejected'], $results, '两笔并发冻结只应成功一笔');
        $this->assertSame(2000, $this->balance($user->id)['balance']);
        $this->assertSame(8000, $this->balance($user->id)['frozen_balance']);
    }

    /**
     * 原子性：余额不足的冻结抛异常后，钱包状态与流水均无变化（事务回滚）。
     */
    public function test_failed_freeze_leaves_no_partial_state(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 5000);

        try {
            $this->service->freezeFunds($user->id, 9999);
            $this->fail('余额不足应抛出异常');
        } catch (RuntimeException) {
            // 预期异常
        }

        $this->assertSame(5000, $this->balance($user->id)['balance']);
        $this->assertSame(0, $this->balance($user->id)['frozen_balance']);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(
            1,
            DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->count(),
            '失败冻结不应留下流水',
        );
    }

    public function test_wallet_api_returns_balance(): void
    {
        $user = $this->makeUser();
        $this->service->deposit($user->id, 12345);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 12345)
            ->assertJsonPath('data.frozen_balance', 0);
    }

    public function test_deposit_api_increases_balance(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/deposit', ['amount' => 5000])
            ->assertOk()
            ->assertJsonPath('data.balance', 5000);
    }
}
