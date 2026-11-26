<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\AdminSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminSystemController extends Controller
{
    public function __construct(
        private readonly AdminSystemService $service,
    ) {}

    /**
     * 系统健康检查.
     */
    public function apiHealth(): JsonResponse
    {
        $health = $this->service->health();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $health,
        ]);
    }

    /**
     * 队列状态（待处理 + 失败任务）.
     */
    public function apiQueueStats(): JsonResponse
    {
        $stats = $this->service->queueStats();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'pending' => $stats['pending'],
                'failed' => collect($stats['failed'])->map(fn ($f): array => [
                    'id' => $f->id,
                    'uuid' => $f->uuid,
                    'queue' => $f->queue,
                    'exception' => mb_substr($f->exception, 0, 500),
                    'failed_at' => $f->failed_at,
                ])->all(),
            ],
        ]);
    }

    /**
     * 重试失败任务.
     */
    public function apiRetryJob(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuid' => 'required|string',
        ]);

        $this->service->retryFailedJob($data['uuid']);

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['uuid' => $data['uuid']],
        ]);
    }

    /**
     * 备份列表.
     */
    public function apiBackups(): JsonResponse
    {
        $backups = $this->service->backups();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $backups,
        ]);
    }

    /**
     * 最近日志.
     */
    public function apiLogs(): JsonResponse
    {
        $lines = $this->service->tailLog(100);

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $lines,
        ]);
    }
}
