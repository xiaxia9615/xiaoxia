<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\AdminSystemService;
use App\Modules\System\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminSystemController extends Controller
{
    public function __construct(
        private readonly AdminSystemService $service,
    ) {}

    public function health(): View
    {
        return view('admin.system.health', ['health' => $this->service->health()]);
    }

    public function backups(): View
    {
        $backups = $this->service->backups();

        $rows = array_map(fn ($b): array => [
            e($b['name']),
            number_format($b['size'] / 1024, 1).' KB',
            e($b['mtime']),
        ], $backups);

        return view('admin.system.backups', [
            'backups' => $backups,
            'rows' => $rows,
        ]);
    }

    public function logs(): View
    {
        return view('admin.system.logs', ['lines' => $this->service->tailLog(100)]);
    }

    public function queues(): View
    {
        $stats = $this->service->queueStats();

        $failedRows = $stats['failed']->map(fn ($f): array => [
            $f->id,
            e((string) $f->uuid),
            e((string) $f->queue),
            e((string) $f->failed_at),
            '<form method="POST" action="'.route('admin.system.queues.retry', $f->uuid).'" class="inline">'
            .csrf_field()
            .'<button type="submit" class="text-indigo-600 hover:text-indigo-800">重试</button></form>',
        ])->all();

        return view('admin.system.queues', [
            'pending' => $stats['pending'],
            'failedRows' => $failedRows,
            'failedCount' => $stats['failed']->count(),
        ]);
    }

    public function retryJob(Request $request, AuditService $auditService, string $uuid): RedirectResponse
    {
        $this->service->retryFailedJob($uuid);

        $auditService->log(
            (int) $request->user()->id,
            'admin.system.queue_retry',
            'failed_job',
            null,
            ['uuid' => $uuid],
        );

        return back()->with('status', "任务 {$uuid} 已重新加入队列");
    }
}
