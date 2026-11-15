<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ApiKey\Services\AdminApiKeyService;
use App\Modules\System\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminApiKeyController extends Controller
{
    public function __construct(
        private readonly AdminApiKeyService $service,
        private readonly AuditService $auditService,
    ) {}

    public function index(): View
    {
        $keys = $this->service->paginateKeys();

        $rows = $keys->getCollection()->map(function ($k): array {
            $statusBadge = $k->status === 1
                ? '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-sm text-slate-600">active</span></span>'
                : '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span><span class="text-sm text-slate-600">revoked</span></span>';

            $action = $k->status !== 0
                ? '<form method="POST" action="'.route('admin.api-keys.revoke', $k->id).'" '
                  .'onsubmit="return confirm(\'确认吊销此 Key？吊销后立即失效。\')" class="inline">'
                  .csrf_field().method_field('DELETE')
                  .'<button type="submit" class="text-red-600 hover:underline text-sm">吊销</button></form>'
                : '<span class="text-slate-400 text-sm">已吊销</span>';

            return [
                $k->id,
                e((string) ($k->user_email ?? 'user#'.$k->user_id)),
                e((string) $k->name),
                '<code class="text-xs">'.e((string) $k->key_prefix).'****</code>',
                $statusBadge,
                $k->last_used_at ? e((string) $k->last_used_at) : '<span class="text-slate-400">未使用</span>',
                $k->expires_at ? e((string) $k->expires_at) : '<span class="text-slate-400">永不过期</span>',
                $action,
            ];
        })->all();

        return view('admin.api-keys.index', [
            'keys' => $keys,
            'rows' => $rows,
            'stats' => $this->service->stats(),
        ]);
    }

    public function revoke(int $id): RedirectResponse
    {
        $ok = $this->service->revoke($id);

        if ($ok) {
            $this->auditService->log(
                (int) auth()->id(),
                'admin.apikey.revoke',
                'api_key',
                $id,
            );
        }

        return redirect()
            ->route('admin.api-keys.index')
            ->with($ok ? 'success' : 'error', $ok ? '已吊销' : '吊销失败（不存在或已吊销）');
    }

    /**
     * API Key 列表（JSON，key 脱敏输出：仅 key_prefix + ****）.
     */
    public function apiIndex(): JsonResponse
    {
        $keys = $this->service->paginateKeys();

        $list = collect($keys->items())->map(fn ($k): array => [
            'id' => $k->id,
            'user_id' => $k->user_id,
            'user_email' => $k->user_email,
            'name' => $k->name,
            'masked_key' => ($k->key_prefix ?? '').'****',
            'status' => (int) $k->status,
            'last_used_at' => $k->last_used_at,
            'expires_at' => $k->expires_at,
            'created_at' => $k->created_at,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $keys->total(),
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
                'per_page' => $keys->perPage(),
            ],
        ]);
    }

    /**
     * API 吊销 Key（JSON）.
     */
    public function apiRevoke(Request $request, int $id): JsonResponse
    {
        $ok = $this->service->revoke($id);

        if (! $ok) {
            return response()->json([
                'code' => 1,
                'msg' => '吊销失败（不存在或已吊销）',
                'data' => null,
            ], 422);
        }

        $this->auditService->log(
            (int) $request->user()->id,
            'admin.apikey.revoke',
            'api_key',
            $id,
        );

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['id' => $id, 'status' => 0],
        ]);
    }
}
