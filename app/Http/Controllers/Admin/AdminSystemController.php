<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\AdminSystemService;
use Illuminate\Contracts\View\View;

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
            number_format($b['size'] / 1024, 1) . ' KB',
            e($b['mtime']),
        ], $backups);

        return view('admin.system.backups', [
            'backups' => $backups,
            'rows'    => $rows,
        ]);
    }

    public function logs(): View
    {
        return view('admin.system.logs', ['lines' => $this->service->tailLog(100)]);
    }
}
