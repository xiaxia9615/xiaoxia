<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserProfileController extends Controller
{
    /**
     * 获取当前用户资料.
     */
    public function profile(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'avatar' => $u->avatar,
                'provider' => $u->provider,
                'is_admin' => (bool) $u->is_admin,
                'is_finance' => (bool) $u->is_finance,
                'is_active' => (bool) $u->is_active,
                'is_developer' => (bool) $u->is_developer,
                'developer_status' => $u->developer_status instanceof \BackedEnum
                    ? $u->developer_status->value
                    : (string) $u->developer_status,
                'created_at' => $u->created_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * 修改用户资料（name / phone / avatar）.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string|max:500',
        ]);

        $u = $request->user();

        if (isset($data['name'])) {
            $u->name = $data['name'];
        }
        if (array_key_exists('phone', $data)) {
            $u->phone = $data['phone'];
        }
        if (array_key_exists('avatar', $data)) {
            $u->avatar = $data['avatar'];
        }
        $u->save();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'avatar' => $u->avatar,
            ],
        ]);
    }

    /**
     * 修改密码（需验证旧密码）.
     */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:32',
        ]);

        $u = $request->user();

        if (! Hash::check($data['current_password'], $u->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['当前密码不正确'],
            ]);
        }

        $u->password = $data['new_password'];
        $u->save();

        // 撤销所有现有 token（强制重新登录）
        $u->tokens()->delete();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [],
        ]);
    }

    /**
     * 用户积分/余额概览.
     */
    public function integral(Request $request): JsonResponse
    {
        $u = $request->user();

        // 从 Wallet 模块获取余额（如果存在）
        $wallet = \App\Modules\Wallet\Models\Wallet::where('user_id', $u->id)->first();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'balance' => $wallet ? (float) $wallet->balance : 0.0,
                'frozen_balance' => $wallet ? (float) $wallet->frozen_balance : 0.0,
                'points' => 0, // 积分系统预留
                'list' => [],
                'total' => 0,
            ],
        ]);
    }

    /**
     * 重置密码（通过邮箱验证码或管理员操作）.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6|max:32',
        ]);

        // 验证短信/邮箱验证码（从缓存中读取）
        $cacheKey = 'pwd_reset:'.$data['email'];
        $cachedCode = cache()->get($cacheKey);

        if ($cachedCode === null || $cachedCode !== $data['code']) {
            return response()->json([
                'code' => 1,
                'msg' => '验证码无效或已过期',
            ], 422);
        }

        $user = \App\Models\User::where('email', $data['email'])->first();
        if ($user === null) {
            return response()->json([
                'code' => 1,
                'msg' => '用户不存在',
            ], 404);
        }

        $user->password = $data['new_password'];
        $user->save();
        $user->tokens()->delete();
        cache()->forget($cacheKey);

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [],
        ]);
    }
}
