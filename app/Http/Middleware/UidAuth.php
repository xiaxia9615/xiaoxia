<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 统一认证中间件：兼容 Sanctum Token 与 UID 签名两种方式。
 *
 * 优先级：
 * 1. Bearer Token（Sanctum）— 已有 API Key 调用方无需改动
 * 2. X-Uid + X-Sign + X-Timestamp + X-Nonce — 前端开发阶段轻量认证
 *
 * 签名算法：HMAC-SHA256(uid + timestamp + nonce, APP_KEY)
 * 防重放：nonce 缓存 10 分钟，同一 nonce 不可重复使用
 * 防篡改：timestamp 有效期 5 分钟
 */
final class UidAuth
{
    private const TIMESTAMP_TTL_SECONDS = 300; // 5 分钟

    private const NONCE_TTL_SECONDS = 600; // 10 分钟

    public function handle(Request $request, Closure $next): Response
    {
        // 1. 优先尝试 Sanctum Bearer Token
        $bearerToken = $request->bearerToken();
        if (is_string($bearerToken) && $bearerToken !== '') {
            $user = $this->resolveSanctumUser($request);
            if ($user !== null) {
                return $next($request);
            }
        }

        // 1b. 兼容 actingAs（测试环境）：auth guard 已有用户时直接放行
        $guardUser = auth('sanctum')->user();
        if ($guardUser instanceof User) {
            $request->setUserResolver(fn (): User => $guardUser);

            return $next($request);
        }

        // 2. UID 签名认证
        $uid = $request->header('X-Uid');
        $sign = $request->header('X-Sign');
        $timestamp = $request->header('X-Timestamp');
        $nonce = $request->header('X-Nonce');

        if (! is_string($uid) || ! is_string($sign) || ! is_string($timestamp) || ! is_string($nonce)) {
            return response()->json([
                'code' => 401,
                'msg' => '未提供有效的认证信息',
                'data' => null,
            ], 401);
        }

        // 校验 uid
        $uidInt = (int) $uid;
        if ($uidInt <= 0) {
            return response()->json(['code' => 401, 'msg' => '无效的用户 ID', 'data' => null], 401);
        }

        // 校验时间戳（5 分钟有效期）
        $tsInt = (int) $timestamp;
        if (abs(time() - $tsInt) > self::TIMESTAMP_TTL_SECONDS) {
            return response()->json(['code' => 401, 'msg' => '请求已过期', 'data' => null], 401);
        }

        // 防重放：nonce 不可重复使用
        $nonceKey = "uid_auth_nonce:{$nonce}";
        if (Cache::has($nonceKey)) {
            Log::warning('UidAuth nonce replay detected', [
                'uid' => $uidInt,
                'nonce' => $nonce,
            ]);

            return response()->json(['code' => 401, 'msg' => '请求不可重复', 'data' => null], 401);
        }

        // 校验签名：HMAC-SHA256(uid + timestamp + nonce, APP_KEY)
        $expectedSign = hash_hmac('sha256', $uid.$timestamp.$nonce, config('app.key'));
        if (! hash_equals($expectedSign, $sign)) {
            Log::warning('UidAuth signature mismatch', [
                'uid' => $uidInt,
                'nonce' => $nonce,
            ]);

            return response()->json(['code' => 401, 'msg' => '签名校验失败', 'data' => null], 401);
        }

        // 查询用户
        $user = User::find($uidInt);
        if ($user === null) {
            return response()->json(['code' => 401, 'msg' => '用户不存在', 'data' => null], 401);
        }

        if (! $user->is_active) {
            return response()->json(['code' => 403, 'msg' => '账号已被禁用', 'data' => null], 403);
        }

        // 标记 nonce 已使用
        Cache::put($nonceKey, 1, self::NONCE_TTL_SECONDS);

        // 注入用户到 request
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    /**
     * 尝试通过 Sanctum 解析用户
     */
    private function resolveSanctumUser(Request $request): ?User
    {
        try {
            $guard = auth('sanctum');
            $user = $guard->user();

            if ($user instanceof User) {
                $request->setUserResolver(fn () => $user);

                return $user;
            }
        } catch (\Throwable) {
            // Sanctum 解析失败，静默降级到 UID 签名
        }

        return null;
    }
}
