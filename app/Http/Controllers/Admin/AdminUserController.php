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
    public function index(AdminUserService $service)
    {
        $users = $service->paginateUsers();

        return view('admin.users.index', ['users' => $users]);
    }

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

    public function show(AdminUserService $service, int $id)
    {
        $user = $service->findUser($id);

        // 关联统计在 Controller 层查询（避免 User 模块反向依赖 ApiKey 模块形成循环依赖）
        $stats = [
            'api_keys_count' => ApiKey::query()->where('user_id', $id)->count(),
            'api_calls_count' => ApiCall::query()->where('user_id', $id)->count(),
        ];

        return view('admin.users.show', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function updateRole(Request $request, AdminUserService $service, AuditService $auditService, int $id)
    {
        $data = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ]);

        try {
            $service->updateRole((int) $request->user()->id, $id, $data['role']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        Log::info('admin.update_role', [
            'operator' => $request->user()->id,
            'target' => $id,
            'role' => $data['role'],
            'ip' => $request->ip(),
        ]);

        $auditService->log(
            (int) $request->user()->id,
            'admin.update_role',
            'user',
            $id,
            ['role' => $data['role']],
        );

        return back()->with('status', '角色已更新');
    }

    public function toggle(Request $request, AdminUserService $service, AuditService $auditService, int $id)
    {
        try {
            $active = $service->toggleActive((int) $request->user()->id, $id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['toggle' => $e->getMessage()]);
        }

        Log::info('admin.toggle_active', [
            'operator' => $request->user()->id,
            'target' => $id,
            'active' => $active,
            'ip' => $request->ip(),
        ]);

        $auditService->log(
            (int) $request->user()->id,
            'admin.toggle_active',
            'user',
            $id,
            ['active' => $active],
        );

        return back()->with('status', $active ? '已启用' : '已禁用');
    }
}
