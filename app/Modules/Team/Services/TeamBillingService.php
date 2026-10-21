<?php

declare(strict_types=1);

namespace App\Modules\Team\Services;

use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Team\Dto\BillingContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * 团队计费服务：团队共享 API Key 调用时扣团队 owner 公户，员工个人余额不变。
 *
 * 扣费事务完全复用 LedgerService::debit()（悲观锁 + 余额校验 + 复式记账），
 * 本服务不重开扣费事务，只负责：幂等、成员校验、审计落库。
 */
final class TeamBillingService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * 团队计费：扣 billing_owner_id 的 balance 账户，记录 actor_user_id。
     *
     * @param  positive-int  $amount  扣费金额（单位：毫）
     * @return array{team_id:int, actor_user_id:int, billing_owner_id:int, api_key_id:int, request_id:string, amount:int, ledger_transaction_id:string}
     *
     * @throws \RuntimeException 非团队成员、余额不足或扣减金额非法
     */
    public function charge(BillingContext $ctx, int $amount): array
    {
        // 幂等第一层：先按 request_id 查审计记录，命中直接返回，不重复扣费
        $existing = $this->findByRequestId($ctx->requestId);
        if ($existing !== null) {
            return $existing;
        }

        // 成员校验：actor_user_id 必须是 team_id 的在册成员，否则拒绝且零扣费
        $isMember = DB::table('team_user')
            ->where('team_id', $ctx->teamId)
            ->where('user_id', $ctx->actorUserId)
            ->exists();
        if (! $isMember) {
            throw new \RuntimeException('非团队成员，拒绝扣费: team='.$ctx->teamId.' actor='.$ctx->actorUserId);
        }

        // 扣团队 owner 公户：复用 LedgerService::debit()（悲观锁 + 余额校验 + 复式记账），
        // 余额不足时 debit() 抛 RuntimeException 令事务回滚，此处不吞异常、不落审计记录
        $transactionId = $this->ledger->debit(
            'user',
            $ctx->billingOwnerId,
            'balance',
            $amount,
            'team_api_call',
            $ctx->teamId,
        );

        // 审计落库：team_id / actor_user_id / billing_owner_id / api_key_id / request_id / amount / ledger_transaction_id
        try {
            DB::table('team_billing_records')->insert([
                'team_id'                => $ctx->teamId,
                'actor_user_id'          => $ctx->actorUserId,
                'billing_owner_id'       => $ctx->billingOwnerId,
                'api_key_id'             => $ctx->apiKeyId,
                'request_id'             => $ctx->requestId,
                'amount'                 => $amount,
                'ledger_transaction_id'  => $transactionId,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // 幂等第二层：并发下唯一索引兜底，按 request_id 回查返回已有记录
            $existing = $this->findByRequestId($ctx->requestId);
            if ($existing !== null) {
                return $existing;
            }
            throw new \RuntimeException('幂等记录冲突但无法回查: request_id='.$ctx->requestId);
        }

        return [
            'team_id'                => $ctx->teamId,
            'actor_user_id'          => $ctx->actorUserId,
            'billing_owner_id'       => $ctx->billingOwnerId,
            'api_key_id'             => $ctx->apiKeyId,
            'request_id'             => $ctx->requestId,
            'amount'                 => $amount,
            'ledger_transaction_id'  => $transactionId,
        ];
    }

    /**
     * 按 request_id 查询审计记录，不存在返回 null。
     *
     * @return array{team_id:int, actor_user_id:int, billing_owner_id:int, api_key_id:int, request_id:string, amount:int, ledger_transaction_id:string}|null
     */
    private function findByRequestId(string $requestId): ?array
    {
        $row = DB::table('team_billing_records')->where('request_id', $requestId)->first();
        if ($row === null) {
            return null;
        }

        return [
            'team_id'                => (int) $row->team_id,
            'actor_user_id'          => (int) $row->actor_user_id,
            'billing_owner_id'       => (int) $row->billing_owner_id,
            'api_key_id'             => (int) $row->api_key_id,
            'request_id'             => (string) $row->request_id,
            'amount'                 => (int) $row->amount,
            'ledger_transaction_id'  => (string) $row->ledger_transaction_id,
        ];
    }
}
