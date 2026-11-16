<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class AlipayController
{
    private const FRONTEND_CALLBACK = 'https://xiaoxia.ren/#/auth/callback';

    private const PROVIDER = 'alipay';

    private const GATEWAY = 'https://openapi.alipay.com/gateway.do';

    /**
     * 重定向到支付宝授权页面。
     */
    public function redirect(): RedirectResponse|JsonResponse
    {
        $appId = (string) config('services.alipay.app_id', '');
        $redirectUri = (string) config('services.alipay.redirect', '');

        if ($appId === '' || $redirectUri === '') {
            return response()->json(['code' => 1, 'message' => '支付宝登录配置异常'], 500);
        }

        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm'
            .'?app_id='.urlencode($appId)
            .'&scope=auth_user'
            .'&redirect_uri='.urlencode($redirectUri);

        return redirect($url);
    }

    /**
     * 支付宝授权回调：接收 auth_code，换取 user_id 和用户信息。
     */
    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $authCode = $request->query('auth_code');

        if (! $authCode) {
            return response()->json(['code' => 1, 'message' => '授权码缺失'], 400);
        }

        try {
            // 第一步：用 auth_code 换取 access_token 和 user_id
            $tokenResult = $this->alipayRequest('alipay.system.oauth.token', [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
            ]);

            $userId = $tokenResult['user_id'] ?? null;
            $accessToken = $tokenResult['access_token'] ?? null;

            if (! $userId || ! $accessToken) {
                return response()->json(['code' => 1, 'message' => '支付宝授权失败'], 500);
            }

            // 第二步：获取用户信息（昵称、头像）
            $userInfo = $this->alipayRequest('alipay.user.info.share', [
                'auth_token' => $accessToken,
            ]);

            $nickName = $userInfo['nick_name'] ?? '支付宝用户';
            $avatar = $userInfo['avatar'] ?? null;
        } catch (RuntimeException $e) {
            Log::error('Alipay callback failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json(['code' => 1, 'message' => '支付宝授权异常，请重试'], 500);
        }

        $user = User::where('provider', self::PROVIDER)
            ->where('provider_id', $userId)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $nickName,
                'email' => null,
                'password' => null,
                'provider' => self::PROVIDER,
                'provider_id' => $userId,
                'avatar' => $avatar,
                'status' => 1,
            ]);
        } else {
            $user->avatar = $avatar;
            $user->save();
        }

        if (! $user->is_active || (int) $user->status !== 1) {
            return response()->json(['code' => 1, 'message' => 'account disabled'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return redirect(self::FRONTEND_CALLBACK.'?token='.$token.'&redirect=/');
    }

    /**
     * 向支付宝网关发送请求（手写 RSA2 签名）。
     *
     * @param  array<string, string>  $bizParams
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function alipayRequest(string $method, array $bizParams): array
    {
        $appId = (string) config('services.alipay.app_id', '');
        $privateKey = (string) config('services.alipay.private_key', '');
        $publicKey = (string) config('services.alipay.public_key', '');

        if ($appId === '' || $privateKey === '' || $publicKey === '') {
            throw new RuntimeException('支付宝配置不完整');
        }

        $params = [
            'app_id' => $appId,
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($bizParams, JSON_UNESCAPED_UNICODE),
        ];

        $params['sign'] = $this->generateSign($params, $privateKey);

        $response = Http::connectTimeout(5)->timeout(15)->asForm()->post(self::GATEWAY, $params);

        if (! $response->ok()) {
            throw new RuntimeException('支付宝网关请求失败: HTTP '.$response->status());
        }

        $data = $response->json();
        $responseKey = str_replace('.', '_', $method).'_response';
        $result = $data[$responseKey] ?? [];

        if (isset($result['code']) && $result['code'] !== '10000') {
            $msg = $result['sub_msg'] ?? $result['msg'] ?? '未知错误';
            throw new RuntimeException('支付宝接口错误: '.$msg);
        }

        return $result;
    }

    /**
     * 生成 RSA2 签名。
     *
     * @param  array<string, string>  $params
     */
    private function generateSign(array $params, string $privateKey): string
    {
        // 移除 sign 和 sign_type，按 key 升序排列
        unset($params['sign'], $params['sign_type']);
        ksort($params);

        $pairs = [];
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) { // @phpstan-ignore-line
                continue;
            }
            $pairs[] = $k.'='.$v;
        }
        $signContent = implode('&', $pairs);

        $key = "-----BEGIN RSA PRIVATE KEY-----\n".
            wordwrap($privateKey, 64, "\n", true).
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign($signContent, $signature, $key, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }
}
