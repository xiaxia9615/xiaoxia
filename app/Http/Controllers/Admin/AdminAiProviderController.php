<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\AiProvider\Services\AdminAiProviderService;
use Illuminate\Contracts\View\View;

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
        $rows = $providers->getCollection()->map(fn ($p): array => [
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
            'rows'     => $rows,
        ]);
    }

    /**
     * 某供应商的模型列表.
     */
    public function models(int $providerId): View
    {
        $models = $this->service->paginateModels($providerId);

        $rows = $models->getCollection()->map(fn ($m): array => [
            $m->id,
            e((string) $m->name),
            e((string) ($m->display_name ?? '-')),
            $m->input_price_per_1k,
            $m->output_price_per_1k,
            $m->enabled ? '启用' : '停用',
        ])->all();

        return view('admin.ai-providers.models', [
            'providerId' => $providerId,
            'models'     => $models,
            'rows'      => $rows,
        ]);
    }
}
