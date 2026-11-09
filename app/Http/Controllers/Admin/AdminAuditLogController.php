<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = DB::table('audit_logs')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->query('action')))
            ->when($request->filled('operator_id'), fn ($q) => $q->where('operator_id', (int) $request->query('operator_id')))
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->query('target_type')))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->all());

        $rows = $logs->getCollection()->map(fn ($log): array => [
            $log->id,
            e((string) $log->action),
            e((string) ($log->target_type ?? '-')),
            e((string) ($log->target_id ?? '-')),
            e((string) ($log->operator_id ?? '-')),
            '<code class="text-xs text-slate-500">'.e((string) ($log->ip ?? '-')).'</code>',
            e($log->created_at ? Carbon::parse($log->created_at)->format('Y-m-d H:i:s') : '-'),
        ])->all();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'rows' => $rows,
        ]);
    }
}
