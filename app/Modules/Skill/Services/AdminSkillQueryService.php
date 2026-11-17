<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 后台管理专用查询服务：负责 skill_details 表的读封装。
 *
 * 仅在 Skill 模块内部使用（AdminSkillService 注入），不对外暴露。
 * products 表联查依赖 Skill→Product 模块依赖（config/modules.php 白名单）。
 */
final class AdminSkillQueryService
{
    /**
     * 技能分页列表（联查产品名与状态），按 ID 倒序。
     */
    public function paginateSkills(int $perPage = 30): LengthAwarePaginator
    {
        return DB::table('skill_details as sd')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->select(
                'sd.id',
                'sd.product_id',
                'p.name as product_name',
                'sd.endpoint',
                'sd.method',
                'sd.pricing_type',
                'sd.api_key',
                'sd.free_quota',
                'p.status as product_status',
            )
            ->orderByDesc('sd.id')
            ->paginate($perPage);
    }

    /**
     * 查询单个技能详情（联查产品名），不存在返回 null。
     */
    public function findSkillDetail(int $id): ?object
    {
        return DB::table('skill_details as sd')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->select(
                'sd.id',
                'sd.product_id',
                'p.name as product_name',
                'sd.endpoint',
                'sd.method',
                'sd.pricing_type',
                'sd.provider_share',
                'sd.platform_share',
                'sd.influencer_share',
                'sd.donation_share',
                'sd.free_quota',
                'sd.request_template',
                'sd.config',
            )
            ->where('sd.id', $id)
            ->first();
    }

    /**
     * 查询技能加密 API Key，不存在返回 null。
     */
    public function findApiKey(int $id): ?string
    {
        $key = DB::table('skill_details')->where('id', $id)->value('api_key');

        return $key !== null ? (string) $key : null;
    }

    /**
     * 技能总数。
     */
    public function countSkills(): int
    {
        return DB::table('skill_details')->count();
    }
}
