<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\Skill\Services\AdminSkillService;
use App\Modules\System\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminSkillController extends Controller
{
    public function __construct(
        private readonly AdminSkillService $service,
    ) {}

    public function index(): View
    {
        $skills = $this->service->paginateSkills();
        $rows = $skills->getCollection()->map(fn ($s): array => [
            $s->id,
            e((string) ($s->product_name ?? '-')),
            e((string) $s->endpoint),
            e((string) $s->method),
            e((string) $s->pricing_type),
            '<code>'.e($s->masked_api_key).'</code>',
            $s->calls_count,
            '<a href="'.route('admin.skills.show', $s->id).'" class="text-indigo-600 hover:text-indigo-800">详情</a> | '
            .'<a href="'.route('admin.skills.edit', $s->id).'" class="text-indigo-600 hover:text-indigo-800">编辑</a> | '
            .'<form method="POST" action="'.route('admin.skills.toggle', $s->id).'" class="inline">'
            .csrf_field().method_field('PATCH')
            .'<button type="submit" class="text-indigo-600 hover:text-indigo-800">上下架</button></form>',
        ])->all();

        return view('admin.skills.index', [
            'stats' => $this->service->stats(),
            'skills' => $skills,
            'rows' => $rows,
        ]);
    }

    public function calls(Request $request): View
    {
        $calls = $this->service->paginateCalls(30, $request->query('status'));
        $rows = $calls->getCollection()->map(fn ($c): array => [
            e((string) $c->created_at),
            e((string) $c->request_id),
            $c->user_id,
            $c->product_id,
            $c->status_code === 200
                ? '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-sm text-slate-600">'.$c->status_code.'</span></span>'
                : '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span><span class="text-sm text-slate-600">'.$c->status_code.'</span></span>',
            $c->duration_ms.'ms',
            number_format($c->cost / 100, 4),
            '-',
        ])->all();

        return view('admin.skills.calls', [
            'calls' => $calls,
            'rows' => $rows,
            'filters' => ['status' => $request->query('status')],
        ]);
    }

    public function show(int $id): View
    {
        $skill = $this->service->getSkill($id);
        if (! $skill) {
            abort(404);
        }

        return view('admin.skills.show', ['skill' => $skill]);
    }

    public function create(): View
    {
        $providers = AiProvider::query()->where('enabled', true)->orderBy('display_name')->get();

        return view('admin.skills.create', ['providers' => $providers]);
    }

    public function store(Request $request, AuditService $auditService): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.001',
            'status' => 'nullable|integer|in:0,1',
            'provider_id' => 'nullable|integer|exists:ai_providers,id',
            'system_prompt' => 'nullable|string|max:5000',
            'endpoint' => 'nullable|string|max:500',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH',
            'pricing_type' => 'nullable|integer|in:1,2',
            'free_quota' => 'nullable|integer|min:0',
        ]);

        [$product, $skillDetail] = $this->service->create($data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.skill.create',
            'skill',
            $skillDetail->id,
            ['name' => $product->name, 'price' => $product->price, 'provider_id' => $skillDetail->provider_id],
        );

        return redirect()->route('admin.skills.index')
            ->with('status', "技能 {$product->name} 已创建");
    }

    public function edit(int $id): View
    {
        $skillDetail = $this->service->findForEdit($id);
        $providers = AiProvider::query()->where('enabled', true)->orderBy('display_name')->get();

        return view('admin.skills.edit', [
            'skillDetail' => $skillDetail,
            'providers' => $providers,
        ]);
    }

    public function update(Request $request, AuditService $auditService, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.001',
            'provider_id' => 'nullable|integer|exists:ai_providers,id',
            'system_prompt' => 'nullable|string|max:5000',
            'endpoint' => 'nullable|string|max:500',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH',
            'pricing_type' => 'nullable|integer|in:1,2',
            'free_quota' => 'nullable|integer|min:0',
        ]);

        [$product, $skillDetail] = $this->service->update($id, $data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.skill.update',
            'skill',
            $skillDetail->id,
            ['name' => $product->name, 'price' => $product->price, 'provider_id' => $skillDetail->provider_id],
        );

        return redirect()->route('admin.skills.index')
            ->with('status', "技能 {$product->name} 已更新");
    }

    public function toggleStatus(Request $request, AuditService $auditService, int $id): RedirectResponse
    {
        $product = $this->service->toggleStatus($id);
        $label = $product->status === 1 ? '上架' : '下架';

        $auditService->log(
            (int) $request->user()->id,
            'admin.skill.toggle_status',
            'product',
            $product->id,
            ['name' => $product->name, 'status' => $product->status],
        );

        return back()->with('status', "技能 {$product->name} 已{$label}");
    }

    /**
     * API 技能列表（JSON，api_key 脱敏输出）.
     */
    public function apiIndex(): JsonResponse
    {
        $skills = $this->service->paginateSkills();

        $list = collect($skills->items())->map(fn ($s): array => [
            'id' => $s->id,
            'product_id' => $s->product_id,
            'product_name' => $s->product_name,
            'endpoint' => $s->endpoint,
            'method' => $s->method,
            'pricing_type' => (int) $s->pricing_type,
            'masked_api_key' => $s->masked_api_key,
            'free_quota' => $s->free_quota,
            'product_status' => (int) $s->product_status,
            'calls_count' => $s->calls_count,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $skills->total(),
                'current_page' => $skills->currentPage(),
                'last_page' => $skills->lastPage(),
                'per_page' => $skills->perPage(),
            ],
        ]);
    }

    /**
     * API 切换技能上下架状态（JSON）.
     */
    public function apiToggleStatus(Request $request, AuditService $auditService, int $id): JsonResponse
    {
        $product = $this->service->toggleStatus($id);

        $auditService->log(
            (int) $request->user()->id,
            'admin.skill.toggle_status',
            'product',
            $product->id,
            ['name' => $product->name, 'status' => $product->status],
        );

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $id,
                'product_id' => $product->id,
                'status' => $product->status,
            ],
        ]);
    }
}
