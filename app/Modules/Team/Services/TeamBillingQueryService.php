<?php

declare(strict_types=1);

namespace App\Modules\Team\Services;

use Illuminate\Support\Facades\DB;

/**
 * 团队计费数据访问查询服务：封装 team_user / team_billing_records 表操作。
 *
 * 仅在 Team 模块内部使用（TeamBillingService 注入），不对外暴露，
 * 业务规则（幂等、原子事务、成员校验）保留在 TeamBillingService。
 */
final class TeamBillingQueryService
{
    /**
     * 校验用户是否为团队在册成员。
     */
    public function isMember(int $teamId, int $userId): bool
    {
        return DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * 插入计费审计记录（须在事务内调用，与扣费同事务）。
     *
     * @param  array<string, mixed>  $record
     */
    public function insertRecord(array $record): void
    {
        DB::table('team_billing_records')->insert($record);
    }

    /**
     * 按 request_id 查询审计记录原始行，不存在返回 null。
     */
    public function findRecordByRequestId(string $requestId): ?object
    {
        return DB::table('team_billing_records')->where('request_id', $requestId)->first();
    }
}
