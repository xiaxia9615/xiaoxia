<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use Illuminate\Support\Facades\DB;

/**
 * 后台管理专用查询服务：负责 api_keys 表的读写封装。
 *
 * 仅在 ApiKey 模块内部使用（AdminApiKeyService 注入），不对外暴露，
 * 不污染对外的 ApiKeyContract 契约（ISP 原则）。
 */
final class AdminApiKeyQueryService
{
    /**
     * API Key 分页列表（关联用户邮箱），按 ID 倒序。
     */
    public function paginateKeys(int $perPage = 20)
    {
        return DB::table('api_keys as ak')
            ->leftJoin('users as u', 'u.id', '=', 'ak.user_id')
            ->select(
                'ak.id',
                'ak.user_id',
                'ak.name',
                'ak.key_prefix',
                'ak.status',
                'ak.last_used_at',
                'ak.expires_at',
                'ak.created_at',
                'u.email as user_email',
            )
            ->orderByDesc('ak.id')
            ->paginate($perPage);
    }

    /**
     * 吊销指定 Key（仅对未吊销的记录生效），返回是否实际更新。
     */
    public function revokeById(int $id): bool
    {
        $affected = DB::table('api_keys')
            ->where('id', $id)
            ->where('status', '!=', 0)
            ->update(['status' => 0, 'updated_at' => now()]);

        return $affected > 0;
    }

    /**
     * 统计计数：总数 / 启用 / 已吊销 / 已过期。
     *
     * @return array{total: int, active: int, revoked: int, expired: int}
     */
    public function stats(): array
    {
        return [
            'total'   => DB::table('api_keys')->count(),
            'active'  => DB::table('api_keys')->where('status', 1)->count(),
            'revoked' => DB::table('api_keys')->where('status', 0)->count(),
            'expired' => DB::table('api_keys')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
        ];
    }
}
