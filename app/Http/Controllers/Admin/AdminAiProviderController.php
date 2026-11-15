<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\AiProvider\Services\AdminAiProviderService;
use App\Modules\System\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminAiProviderController extends Controller
{
    public function __construct(
        private readonly AdminAiProviderService $service,
    ) {}

    /**
     * 供应商列表（api_key 脱敏）.
     */
    public function index(): View
    {
        $providers = $this->service->paginateProviders();

        // Controller 层整形行数组，api_key 一律脱敏，绝不输出完整 key
        $rows = collect($providers->items())->map(fn ($p): array => [
            $p->id,
            e((string) $p->name),
            e((string) ($p->display_name ?? '-')),
            e((string) $p->driver),
            e((string) $p->model_name),
            $this->service->maskApiKey($p->api_key),
            $p->models_count,
            $p->enabled ? '启用' : '停用',
            '<a href="'.route('admin.ai-providers.models', $p->id).'" class="text-indigo-600 hover:text-indigo-800">查看模型</a>',
        ])->all();

        return view('admin.ai-providers.index', [
            'providers' => $providers,
            'rows' => $rows,
        ]);
    }

    /**
     * 某供应商的模型列表.
     */
    public function models(int $providerId): View
    {
        $models = $this->service->paginateModels($providerId);

        $rows = collect($models->items())->map(fn ($m): array => [
            $m->id,
            e((string) $m->name),
            e((string) ($m->display_name ?? '-')),
            $m->input_price_per_1k,
            $m->output_price_per_1k,
            $m->enabled ? '启用' : '停用',
        ])->all();

        return view('admin.ai-providers.models', [
            'providerId' => $providerId,
            'models' => $models,
            'rows' => $rows,
        ]);
    }

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
