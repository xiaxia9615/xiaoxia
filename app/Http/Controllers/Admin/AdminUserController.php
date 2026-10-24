<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\ApiKey\Models\ApiKey;
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

    public function show(AdminUserService $service, int $id)
    {
        $user = $service->findUser($id);

        // 关联统计在 Controller 层查询（避免 User 模块反向依赖 ApiKey 模块形成循环依赖）
        $stats = [
            'api_keys_count' => ApiKey::query()->where('user_id', $id)->count(),
            'api_calls_count' => ApiCall::query()->where('user_id', $id)->count(),
        ];

        return view('admin.users.show', [
            'user'  => $user,
            'stats' => $stats,
        ]);
    }

    public function updateRole(Request $request, AdminUserService $service, int $id)
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
            'target'   => $id,
            'role'     => $data['role'],
            'ip'       => $request->ip(),
        ]);

        return back()->with('status', '角色已更新');
    }

    public function toggle(Request $request, AdminUserService $service, int $id)
    {
        try {
            $active = $service->toggleActive((int) $request->user()->id, $id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['toggle' => $e->getMessage()]);
        }

        Log::info('admin.toggle_active', [
            'operator' => $request->user()->id,
            'target'   => $id,
            'active'   => $active,
            'ip'       => $request->ip(),
        ]);

        return back()->with('status', $active ? '已启用' : '已禁用');
    }
}
