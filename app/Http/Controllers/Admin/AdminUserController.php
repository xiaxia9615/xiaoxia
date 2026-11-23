<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\System\Services\AuditService;
use App\Modules\User\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /** 禁止封禁的超级管理员 ID */
    private const SUPER_ADMIN_IDS = [1, 2];

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

    /**
     * 封禁/解封用户（切换 is_active 状态）。
     *
     * 防呆：禁止封禁 ID 为 1 和 2 的超级管理员账号。
     */
    public function apiToggleBan(Request $request, AuditService $auditService, int $id): JsonResponse
    {
        if (in_array($id, self::SUPER_ADMIN_IDS, true)) {
            return response()->json([
                'code' => 1,
                'msg' => '禁止操作超级管理员账号',
                'data' => [],
            ], 403);
        }

        $user = User::query()->find($id);

        if (! $user) {
            return response()->json([
                'code' => 1,
                'msg' => '用户不存在',
                'data' => [],
            ], 404);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $action = $user->is_active ? '解封' : '封禁';

        $auditService->log(
            (int) $request->user()?->id,
            'admin.user.toggle_ban',
            'user',
            $user->id,
            ['name' => $user->name, 'is_active' => $user->is_active],
        );

        return response()->json([
            'code' => 0,
            'msg' => "用户 {$user->name} 已{$action}",
            'data' => [
                'id' => $user->id,
                'is_active' => (bool) $user->is_active,
            ],
        ]);
    }
}
