<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Models\User;
use App\Modules\User\Services\AuthService;
use App\Modules\User\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly SmsService $sms,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:50', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:6']);
        try {
            return response()->json(['code' => 0, 'data' => $this->auth->register($data['name'], $data['email'], $data['password'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Auth Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后再试',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        try {
            return response()->json(['code' => 0, 'data' => $this->auth->login($data['email'], $data['password'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            Log::error('Auth Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后再试',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['code' => 0, 'message' => 'ok']);
    }

    public function me(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json(['code' => 0, 'data' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'is_admin' => (bool) $u->is_admin, 'is_finance' => (bool) $u->is_finance, 'is_active' => (bool) $u->is_active]]);
    }

    /**
     * 发送短信验证码（带频控）。
     *
     * 频控规则：同一手机号 60 秒内只能发一次；同一 IP 每小时最多 5 次。
     */
    public function smsSend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'scene' => 'sometimes|string|in:register,login,bind_phone,change_phone',
        ]);

        $phone = $data['phone'];
        $ip = $request->ip();

        $phoneKey = "sms_code:{$phone}";
        $ipKey = "sms_ip:{$ip}";

        // 手机号频控：60 秒内只能发一次
        if (Redis::exists($phoneKey)) {
            $ttl = Redis::ttl($phoneKey);

            return response()->json(['code' => 1, 'message' => '验证码已发送，请 '.max($ttl, 1).' 秒后重试'], 429);
        }

        // IP 频控：每小时最多 5 次
        $ipCount = (int) Redis::get($ipKey);
        if ($ipCount >= 5) {
            return response()->json(['code' => 1, 'message' => '请求过于频繁，请稍后再试'], 429);
        }

        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $this->sms->sendCode($phone, $code, $data['scene'] ?? 'register');
        } catch (\RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 500);
        }

        // 验证码存入 Redis，有效期 5 分钟（300 秒）
        Redis::setex($phoneKey, 300, $code);

        // IP 计数：首次设置时给 1 小时过期
        if ($ipCount === 0) {
            Redis::setex($ipKey, 3600, '1');
        } else {
            Redis::incr($ipKey);
        }

        return response()->json(['code' => 0, 'message' => '验证码已发送']);
    }

    /**
     * 手机号验证码登录（不存在则自动注册）。
     */
    public function smsLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:6',
        ]);

        $phone = $data['phone'];
        $code = $data['code'];
        $phoneKey = "sms_code:{$phone}";

        $cachedCode = Redis::get($phoneKey);

        if (! $cachedCode || $cachedCode !== $code) {
            return response()->json(['code' => 1, 'message' => '验证码错误或已过期'], 400);
        }

        // 校验成功后立即删除，防止复用
        Redis::del($phoneKey);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => '用户'.substr($phone, -4),
                'phone' => $phone,
                'email' => null,
                'password' => null,
                'status' => 1,
            ]);
        }

        if (! $user->is_active || (int) $user->status !== 1) {
            return response()->json(['code' => 1, 'message' => 'account disabled'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['code' => 0, 'data' => [
            'user' => ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone, 'is_admin' => (bool) $user->is_admin, 'is_finance' => (bool) $user->is_finance],
            'token' => $token,
        ]]);
    }
}
