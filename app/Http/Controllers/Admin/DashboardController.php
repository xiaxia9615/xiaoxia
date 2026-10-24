<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $service)
    {
        // Controller 层整形最近活动为简单行数组，Blade 只做纯循环，不在属性里写复杂表达式
        $activityRows = array_map(
            static fn (array $a): array => [
                $a['created_at'],
                e($a['user']),
                e($a['product']),
                '<span class="'.($a['status_code'] < 400 ? 'text-emerald-600' : 'text-red-600').'">'.$a['status_code'].'</span>',
                $a['duration_ms'].' ms',
            ],
            $service->recentActivity()
        );

        return view('admin.dashboard', [
            'health'       => $service->systemHealth(),
            'counts'       => $service->tableCounts(),
            'services'     => $service->serviceStatus(),
            'activityRows' => $activityRows,
        ]);
    }
}
