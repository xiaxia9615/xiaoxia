<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Services;

use App\Modules\AiProvider\Models\AiModel;
use App\Modules\AiProvider\Models\AiProvider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理后台 AI 供应商只读查询服务.
 *
 * 安全约束: api_key 一律脱敏输出（sk-****后4位），绝不返回完整 key。
 */
final class AdminAiProviderService
{
    /**
     * 供应商列表 + 模型数 + 脱敏 key.
     */
    public function paginateProviders(int $perPage = 20): LengthAwarePaginator
    {
        return AiProvider::query()
            ->withCount('models')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 某供应商的模型列表.
     */
    public function paginateModels(int $providerId, int $perPage = 30): LengthAwarePaginator
    {
        return AiModel::query()
            ->where('provider_id', $providerId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 脱敏: sk-****xxxx（只保留末 4 位）.
     */
    public function maskApiKey(?string $key): string
    {
        if ($key === null || $key === '') {
            return '-';
        }

        return 'sk-****'.substr($key, -4);
    }
}
