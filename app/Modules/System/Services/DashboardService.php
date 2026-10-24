<?php

declare(strict_types=1);

namespace App\Modules\System\Services;

use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * 系统健康: db / cache 运行时检查（与 /health 探针同源逻辑）.
     */
    public function systemHealth(): array
    {
        return [
            'db'    => rescue(fn () => DB::select('SELECT 1') !== null, false),
            'cache' => rescue(fn () => Cache::put('admin_probe', 1, 5) !== false, false),
        ];
    }

    /**
     * 核心表行数统计.
     */
    public function tableCounts(): array
    {
        return [
            'users'        => User::count(),
            'products'     => Product::count(),
            'skills'       => SkillDetail::count(),
            'api_keys'     => ApiKey::count(),
            'calls_total'  => ApiCall::count(),
            'calls_today'  => ApiCall::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * 最近活动: 最近 10 条 API 调用.
     */
    public function recentActivity(): array
    {
        return ApiCall::query()
            ->latest('id')
            ->limit(10)
            ->get(['id', 'user_id', 'product_id', 'status_code', 'duration_ms', 'created_at'])
            ->map(fn (ApiCall $call) => [
                'id'          => $call->id,
                'user'        => $call->user?->name ?? ('#' . $call->user_id),
                'product'     => ('#' . $call->product_id),
                'status_code' => $call->status_code,
                'duration_ms' => $call->duration_ms,
                'created_at'  => $call->created_at?->format('m-d H:i'),
            ])
            ->all();
    }

    /**
     * 服务进程状态: nginx / php-fpm / queue-worker.
     */
    public function serviceStatus(): array
    {
        return [
            'nginx'   => $this->systemdActive('nginx'),
            'php-fpm' => $this->systemdActive('php8.5-fpm'),
            'worker'  => $this->workerRunning(),
        ];
    }

    private function systemdActive(string $unit): bool
    {
        $out = shell_exec(sprintf('systemctl is-active %s 2>/dev/null', escapeshellarg($unit)));

        return is_string($out) && trim($out) === 'active';
    }

    private function workerRunning(): bool
    {
        // 方括号技巧: 避免 pgrep -f 匹配到 shell_exec 自身的 sh -c 命令行
        $out = shell_exec("pgrep -fc 'artisan[ ]queue:work' 2>/dev/null");

        return is_string($out) && (int) trim($out) > 0;
    }
}
