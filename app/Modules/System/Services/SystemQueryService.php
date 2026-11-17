<?php

declare(strict_types=1);

namespace App\Modules\System\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * System 数据访问查询服务：封装 audit_logs / jobs / failed_jobs 表操作。
 *
 * 仅在 System 模块内部使用（AuditService / AdminSystemService 注入），不对外暴露。
 * jobs / failed_jobs 为 Laravel 框架队列表，无模块归属。
 */
final class SystemQueryService
{
    /**
     * 插入审计日志。
     *
     * @param  array<string, mixed>  $entry
     */
    public function insertAuditLog(array $entry): void
    {
        DB::table('audit_logs')->insert($entry);
    }

    /**
     * 待处理队列任务数。
     */
    public function countPendingJobs(): int
    {
        return DB::table('jobs')->count();
    }

    /**
     * 最近失败任务列表。
     *
     * @return Collection<int, object>
     */
    public function findFailedJobs(int $limit = 50)
    {
        return DB::table('failed_jobs')
            ->select('id', 'uuid', 'queue', 'exception', 'failed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
