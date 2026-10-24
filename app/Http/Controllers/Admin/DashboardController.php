<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $service)
    {
        return view('admin.dashboard', [
            'health'   => $service->systemHealth(),
            'counts'   => $service->tableCounts(),
            'activity' => $service->recentActivity(),
            'services' => $service->serviceStatus(),
        ]);
    }
}
