<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ApiKey\Services\AdminApiKeyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AdminApiKeyController extends Controller
{
    public function __construct(
        private readonly AdminApiKeyService $service,
    ) {}

    public function index(): View
    {
        $keys = $this->service->paginateKeys();

        $rows = $keys->getCollection()->map(function ($k): array {
            $statusBadge = $k->status === 1
                ? '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-sm text-slate-600">active</span></span>'
                : '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span><span class="text-sm text-slate-600">revoked</span></span>';

            $action = $k->status !== 0
                ? '<form method="POST" action="' . route('admin.api-keys.revoke', $k->id) . '" '
                  . 'onsubmit="return confirm(\'确认吊销此 Key？吊销后立即失效。\')" class="inline">'
                  . csrf_field() . method_field('DELETE')
                  . '<button type="submit" class="text-red-600 hover:underline text-sm">吊销</button></form>'
                : '<span class="text-slate-400 text-sm">已吊销</span>';

            return [
                $k->id,
                e((string) ($k->user_email ?? 'user#' . $k->user_id)),
                e((string) $k->name),
                '<code class="text-xs">' . e((string) $k->key_prefix) . '****</code>',
                $statusBadge,
                $k->last_used_at ? e((string) $k->last_used_at) : '<span class="text-slate-400">未使用</span>',
                $k->expires_at ? e((string) $k->expires_at) : '<span class="text-slate-400">永不过期</span>',
                $action,
            ];
        })->all();

        return view('admin.api-keys.index', [
            'keys'  => $keys,
            'rows'  => $rows,
            'stats' => $this->service->stats(),
        ]);
    }

    public function revoke(int $id): RedirectResponse
    {
        $ok = $this->service->revoke($id);

        return redirect()
            ->route('admin.api-keys.index')
            ->with($ok ? 'success' : 'error', $ok ? '已吊销' : '吊销失败（不存在或已吊销）');
    }
}
