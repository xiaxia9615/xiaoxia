<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

final class SocialiteController
{
    private const FRONTEND_CALLBACK = 'https://xiaoxia.ren/#/auth/callback';

    private const ALLOWED_PROVIDERS = ['github', 'weixin'];

    /**
     * 重定向到第三方授权页面。
     */
    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['code' => 1, 'message' => '不支持的登录方式'], 400);
        }

        try {
            $targetUrl = Socialite::driver($provider)->redirect()->getTargetUrl();

            return redirect()->away($targetUrl);
        } catch (RuntimeException $e) {
            Log::error('Socialite redirect failed: '.$e->getMessage(), [
                'provider' => $provider,
                'exception' => $e,
            ]);

            return response()->json(['code' => 1, 'message' => '第三方登录配置异常'], 500);
        }
    }

    /**
     * 第三方授权回调：查找或创建用户，签发 Token，重定向前端。
     */
    public function callback(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['code' => 1, 'message' => '不支持的登录方式'], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (RuntimeException $e) {
            Log::error('Socialite callback failed: '.$e->getMessage(), [
                'provider' => $provider,
                'exception' => $e,
            ]);

            return response()->json(['code' => 1, 'message' => '授权失败，请重试'], 500);
        }

        $providerId = (string) $socialUser->getId();
        $unionid = (string) ($socialUser->getRaw()['unionid'] ?? '');
        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? '用户';
        $avatar = $socialUser->getAvatar();

        $user = User::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $socialUser->getEmail(),
                'password' => null,
                'provider' => $provider,
                'provider_id' => $providerId,
                'unionid' => $unionid !== '' ? $unionid : null,
                'avatar' => $avatar,
                'status' => 1,
            ]);
        } else {
            // 更新头像和昵称
            $user->avatar = $avatar;
            if ($unionid !== '' && $user->unionid !== $unionid) {
                $user->unionid = $unionid;
            }
            $user->save();
        }

        if (! $user->is_active || (int) $user->status !== 1) {
            return response()->json(['code' => 1, 'message' => 'account disabled'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return redirect(self::FRONTEND_CALLBACK.'?token='.$token.'&redirect=/');
    }
}
