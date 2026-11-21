<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
use App\Modules\System\Services\AuditService;
use App\Modules\User\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    /**
     * API 用户列表：返回 JSON 分页数据，供前端后台使用。
     */
    public function apiIndex(AdminUserService $service)
    {
        $users = $service->paginateUsers();

        $list = collect($users->items())->map(fn ($user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'is_active' => (bool) $user->is_active,
            'created_at' => $user->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
            ],
        ]);
    }

}
