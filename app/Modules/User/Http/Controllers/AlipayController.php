<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Models\User;
use App\Modules\User\Plugins\AlipayMethodPlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Yansongda\Artful\Plugin\ParserPlugin;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Plugin\Alipay\V2\AddPayloadSignaturePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\AddRadarPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\ResponsePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\StartPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\VerifySignaturePlugin;
use Yansongda\Pay\Provider\Alipay;

/**
 * 支付宝 OAuth 登录控制器（基于 yansongda/pay v3 SDK 证书模式）。
 *
 * 统一使用 payment.alipay 证书配置，与 AlipayGateway 共享同一套密钥体系，
 * 消除手写 openssl_sign 导致的私钥格式问题。
 */
final class AlipayController
{
    private const FRONTEND_CALLBACK = 'https://xiaoxia.ren/#/auth/callback';

    private const PROVIDER = 'alipay';

    /**
     * 重定向到支付宝授权页面。
     */
    public function redirect(): RedirectResponse|JsonResponse
    {
        $appId = (string) config('payment.alipay.app_id', '');
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
            ], true);

            $userId = $tokenResult->get('user_id');
            $accessToken = $tokenResult->get('access_token');

            if (! $userId || ! $accessToken) {
                return response()->json(['code' => 1, 'message' => '支付宝授权失败'], 500);
            }

            // 第二步：获取用户信息（昵称、头像）
            $userInfo = $this->alipayRequest('alipay.user.info.share', [
                'auth_token' => $accessToken,
            ], true);

            $nickName = $userInfo->get('nick_name', '支付宝用户');
            $avatar = $userInfo->get('avatar');
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
     * 通过 yansongda/pay v3 SDK 向支付宝网关发送请求。
     *
     * @param  array<string, string>  $params  业务参数
     * @param  bool  $topLevel  OAuth 类 API 参数放在顶层（非 biz_content）
     * @return Collection
     *
     * @throws RuntimeException 网关请求失败或业务错误
     */
    private function alipayRequest(string $method, array $params, bool $topLevel = false)
    {
        $client = $this->payClient();

        $requestParams = array_merge($params, [
            '_method' => $method,
            '_top_level' => $topLevel,
        ]);

        $result = $client->pay([
            StartPlugin::class,
            AlipayMethodPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            VerifySignaturePlugin::class,
            ResponsePlugin::class,
            ParserPlugin::class,
        ], $requestParams);

        $responseKey = str_replace('.', '_', $method).'_response';
        $data = $result->all();
        $bizResult = $data[$responseKey] ?? $data;

        if (isset($bizResult['code']) && $bizResult['code'] !== '10000') {
            $msg = $bizResult['sub_msg'] ?? $bizResult['msg'] ?? '未知错误';
            throw new RuntimeException('支付宝接口错误: '.$msg);
        }

        return collect($bizResult);
    }

    /**
     * 构建 SDK 客户端：校验证书路径后注册配置（与 AlipayGateway 共享同一套证书）。
     *
     * @throws RuntimeException 配置缺失或证书文件不存在
     */
    private function payClient(): Alipay
    {
        $appId = config('payment.alipay.app_id');
        if (! is_string($appId) || $appId === '') {
            throw new RuntimeException('Alipay app_id is not configured');
        }

        $certPaths = [
            'app_secret_cert' => config('payment.alipay.app_secret_cert_path'),
            'app_public_cert_path' => config('payment.alipay.app_public_cert_path'),
            'alipay_public_cert_path' => config('payment.alipay.alipay_public_cert_path'),
            'alipay_root_cert_path' => config('payment.alipay.alipay_root_cert_path'),
        ];

        foreach ($certPaths as $key => $path) {
            if (! is_string($path) || $path === '' || ! file_exists($path)) {
                throw new RuntimeException('Alipay cert file not found: '.$key.' ('.(is_string($path) ? $path : '(null)').')');
            }
        }

        Pay::config([
            'alipay' => [
                'default' => $certPaths + [
                    'app_id' => $appId,
                    'notify_url' => config('payment.alipay.notify_url'),
                ],
            ],
        ]);

        return Pay::alipay();
    }
}
