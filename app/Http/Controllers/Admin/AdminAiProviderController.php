<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\AiProvider\Services\AdminAiProviderService;
use App\Modules\System\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminAiProviderController extends Controller
{
    public function __construct(
        private readonly AdminAiProviderService $service,
    ) {}

    /**
     * API 供应商列表（JSON，api_key 一律脱敏输出）.
     */
    public function apiIndex(): JsonResponse
    {
        $providers = $this->service->paginateProviders();

        $list = collect($providers->items())->map(fn ($p): array => [
            'id' => $p->id,
            'name' => $p->name,
            'display_name' => $p->display_name,
            'driver' => $p->driver,
            'model_name' => $p->model_name,
            'base_url' => $p->base_url,
            'masked_key' => $this->service->maskApiKey($p->api_key),
            'models_count' => $p->models_count,
            'enabled' => (bool) $p->enabled,
            'created_at' => $p->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $providers->total(),
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'per_page' => $providers->perPage(),
            ],
        ]);
    }

    /**
     * API 更新供应商（base_url / api_key / enabled）。
     * 安全约束: api_key 留空时保留原值，绝不盲目覆盖；返回值仅含脱敏 key。
     */
    public function apiUpdate(Request $request, AuditService $auditService, int $id): JsonResponse
    {
        $data = $request->validate([
            'base_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $provider = $this->service->updateProvider($id, $data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.ai_provider.update',
            'ai_provider',
            $provider->id,
            [
                'base_url_updated' => array_key_exists('base_url', $data),
                'api_key_updated' => ($data['api_key'] ?? '') !== '',
                'enabled' => (bool) $provider->enabled,
            ],
        );

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $provider->id,
                'base_url' => $provider->base_url,
                'masked_key' => $this->service->maskApiKey($provider->api_key),
                'enabled' => (bool) $provider->enabled,
            ],
        ]);
    }
}
