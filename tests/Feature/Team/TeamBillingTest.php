<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\User;
use App\Modules\AiProvider\Services\CircuitBreaker;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Team\Dto\BillingContext;
use App\Modules\Team\Models\Team;
use App\Modules\Team\Services\TeamBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TeamBillingTest extends TestCase
{
    use RefreshDatabase;

    private TeamBillingService $billing;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billing = app(TeamBillingService::class);
        $this->ledger  = app(LedgerService::class);
    }

    /**
     * 造一个团队：owner（公户扣费主体）+ 员工 actor，并给 owner 充值。
     *
     * @return array{0: Team, 1: User, 2: User} [team, owner, actor]
     */
    private function makeTeamWithBalance(int $topUpAmount): array
    {
        $owner = User::create([
            'name'     => 'team-owner',
            'email'    => 'owner-'.Str::uuid().'@example.com',
            'password' => bcrypt('password123'),
            'status'   => 1,
        ]);
        $actor = User::create([
            'name'     => 'team-actor',
            'email'    => 'actor-'.Str::uuid().'@example.com',
            'password' => bcrypt('password123'),
            'status'   => 1,
        ]);
        $team = Team::create([
            'name'     => '测试团队',
            'owner_id' => $owner->id,
        ]);
        DB::table('team_user')->insert([
            'team_id'    => $team->id,
            'user_id'    => $owner->id,
            'role'       => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('team_user')->insert([
            'team_id'    => $team->id,
            'user_id'    => $actor->id,
            'role'       => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->topUp($owner, $topUpAmount);

        return [$team, $owner, $actor];
    }

    private function topUp(User $user, int $amount): void
    {
        $this->ledger->record('topup', $user->id, [
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

    private function makeContext(Team $team, User $actor, int $apiKeyId, string $requestId): BillingContext
    {
        return new BillingContext(
            teamId: $team->id,
            actorUserId: $actor->id,
            billingOwnerId: $team->owner_id,
            apiKeyId: $apiKeyId,
            requestId: $requestId,
        );
    }

    public function test_charge_deducts_team_owner_balance_and_writes_audit_record(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);
        $before = $this->ledger->balance('user', $owner->id, 'balance');

        $result = $this->billing->charge($this->makeContext($team, $actor, 77, 'req-'.Str::uuid()), 3_000);

        $this->assertSame($before - 3_000, $this->ledger->balance('user', $owner->id, 'balance'));
        $this->assertSame(1, DB::table('team_billing_records')->count());
        $this->assertNotSame('', $result['ledger_transaction_id']);
        $this->assertSame(
            $result['ledger_transaction_id'],
            (string) DB::table('team_billing_records')->value('ledger_transaction_id'),
        );
    }

    public function test_charge_keeps_actor_personal_balance_unchanged(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);
        $this->topUp($actor, 5_000);
        $actorBefore = $this->ledger->balance('user', $actor->id, 'balance');

        $this->billing->charge($this->makeContext($team, $actor, 77, 'req-'.Str::uuid()), 3_000);

        $this->assertSame($actorBefore, $this->ledger->balance('user', $actor->id, 'balance'));
    }

    public function test_audit_record_contains_team_actor_owner_api_key_and_request_id(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);
        $requestId = 'req-'.Str::uuid();

        $this->billing->charge($this->makeContext($team, $actor, 77, $requestId), 3_000);

        $row = DB::table('team_billing_records')->where('request_id', $requestId)->first();
        $this->assertNotNull($row);
        $this->assertSame($team->id, (int) $row->team_id);
        $this->assertSame($actor->id, (int) $row->actor_user_id);
        $this->assertSame($owner->id, (int) $row->billing_owner_id);
        $this->assertSame(77, (int) $row->api_key_id);
        $this->assertSame(3_000, (int) $row->amount);
    }

    public function test_insufficient_balance_rejects_without_deduction_or_record(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(1_000);
        $before = $this->ledger->balance('user', $owner->id, 'balance');

        try {
            $this->billing->charge($this->makeContext($team, $actor, 77, 'req-'.Str::uuid()), 3_000);
            $this->fail('余额不足应抛出 RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame($before, $this->ledger->balance('user', $owner->id, 'balance'));
            $this->assertSame(0, DB::table('team_billing_records')->count());
        }
    }

    public function test_same_request_id_charges_only_once(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);
        $requestId = 'req-'.Str::uuid();
        $ctx = $this->makeContext($team, $actor, 77, $requestId);

        $first  = $this->billing->charge($ctx, 3_000);
        $second = $this->billing->charge($ctx, 3_000);

        $this->assertSame($first, $second);
        $this->assertSame(1, DB::table('team_billing_records')->count());
        $this->assertSame(7_000, $this->ledger->balance('user', $owner->id, 'balance'));
    }

    public function test_different_request_ids_charge_independently(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);

        $this->billing->charge($this->makeContext($team, $actor, 77, 'req-'.Str::uuid()), 3_000);
        $this->billing->charge($this->makeContext($team, $actor, 77, 'req-'.Str::uuid()), 3_000);

        $this->assertSame(2, DB::table('team_billing_records')->count());
        $this->assertSame(4_000, $this->ledger->balance('user', $owner->id, 'balance'));
    }

    public function test_non_member_actor_is_rejected_without_deduction(): void
    {
        [$team, $owner] = $this->makeTeamWithBalance(10_000);
        $outsider = User::create([
            'name'     => 'outsider',
            'email'    => 'outsider-'.Str::uuid().'@example.com',
            'password' => bcrypt('password123'),
            'status'   => 1,
        ]);
        $before = $this->ledger->balance('user', $owner->id, 'balance');

        try {
            $this->billing->charge($this->makeContext($team, $outsider, 77, 'req-'.Str::uuid()), 3_000);
            $this->fail('非成员应抛出 RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame($before, $this->ledger->balance('user', $owner->id, 'balance'));
            $this->assertSame(0, DB::table('team_billing_records')->count());
        }
    }

    public function test_billing_stays_correct_and_not_duplicated_after_circuit_breaker_open(): void
    {
        [$team, $owner, $actor] = $this->makeTeamWithBalance(10_000);
        $requestId = 'req-'.Str::uuid();
        $ctx = $this->makeContext($team, $actor, 77, $requestId);

        // 模拟上游 Provider 熔断：同一 Provider 连续失败达到阈值（3 次）后熔断标记生效
        $breaker = app(CircuitBreaker::class);
        $breaker->recordFailure(1);
        $breaker->recordFailure(1);
        $breaker->recordFailure(1);
        $this->assertFalse($breaker->isAvailable(1), '前置条件：Provider 1 应已熔断');

        // 熔断降级后（重试/重放同一请求）计费正确且不重复扣费
        $first  = $this->billing->charge($ctx, 3_000);
        $second = $this->billing->charge($ctx, 3_000);

        $this->assertSame($first, $second);
        $this->assertSame(1, DB::table('team_billing_records')->count());
        $this->assertSame(7_000, $this->ledger->balance('user', $owner->id, 'balance'));
    }
}
