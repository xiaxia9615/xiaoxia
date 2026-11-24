<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class WechatMiniController
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $appid = (string) config('services.weixin_mini.appid');
        $secret = (string) config('services.weixin_mini.secret');

        if ($appid === '' || $secret === '') {
            return response()->json(['code' => 1, 'message' => '微信小程序未配置'], 500);
        }

        $res = Http::timeout(5)->get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $validated['code'],
            'grant_type' => 'authorization_code',
        ])->json();

        $openid = $res['openid'] ?? null;
        $unionid = $res['unionid'] ?? null;

        if (! is_string($openid) || $openid === '') {
            return response()->json([
                'code' => 1,
                'message' => is_array($res) ? ($res['errmsg'] ?? '小程序授权失败') : '小程序授权失败',
            ], 400);
        }

        $user = User::where('provider', 'weixin_mini')
            ->where('provider_id', $openid)
            ->first();

        if (! $user && is_string($unionid) && $unionid !== '') {
            $user = User::where('unionid', $unionid)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => '微信用户',
                'email' => null,
                'password' => null,
                'provider' => 'weixin_mini',
                'provider_id' => $openid,
                'unionid' => $unionid,
                'status' => 1,
            ]);
        } elseif (is_string($unionid) && $unionid !== '' && $user->unionid !== $unionid) {
            $user->unionid = $unionid;
            $user->save();
        }

        if (! $user->is_active || (int) $user->status !== 1) {
            return response()->json(['code' => 1, 'message' => 'account disabled'], 403);
        }

        return response()->json([
            'code' => 0,
            'data' => [
                'token' => $user->createToken('wechat_mini')->plainTextToken,
            ],
        ]);
    }
}
