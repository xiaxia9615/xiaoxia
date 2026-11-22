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

/**
 * 支付宝 OAuth 登录控制器（手写签名，绕开 yansongda/pay 插件链）。
 *
 * 使用 payment.alipay 证书配置（公钥证书模式），
 * 手写 RSA2 签名直接调用支付宝网关，避免 SDK 插件链处理顶层参数的缺陷。
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
     * 支付宝授权回调：接收 auth_code，换取 user_id，落地用户并签发 token。
     */
    public function callback(Request $request): RedirectResponse|JsonResponse
    {
        $authCode = $request->query('auth_code');

        if (! $authCode) {
            return response()->json(['code' => 1, 'message' => '授权码缺失'], 400);
        }

        try {
            $tokenData = $this->fetchOAuthToken($authCode);

            $userId = $tokenData['user_id'] ?? null;
            $accessToken = $tokenData['access_token'] ?? null;

            if (! $userId || ! $accessToken) {
                return response()->json(['code' => 1, 'message' => '支付宝授权失败'], 500);
            }

            // 用户昵称/头像暂用默认值（DetailPlugin 后续手写补充）
            $nickName = '支付宝用户';
            $avatar = null;
        } catch (RuntimeException $e) {
            Log::error('Alipay callback failed: '.$e->getMessage(), ['exception' => $e]);

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
     * 手写支付宝 OAuth token 交换（绕开 yansongda/pay 插件链）。
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function fetchOAuthToken(string $authCode): array
    {
        $appId = (string) config('payment.alipay.app_id');
        $privateKeyPath = (string) config('payment.alipay.app_secret_cert_path');
        $appCertPath = (string) config('payment.alipay.app_public_cert_path');
        $rootCertPath = (string) config('payment.alipay.alipay_root_cert_path');

        if ($appId === '' || ! is_file($privateKeyPath)) {
            throw new RuntimeException('支付宝 OAuth 配置缺失：app_id 或私钥文件不存在');
        }

        $privateKey = file_get_contents($privateKeyPath);
        if ($privateKey === false) {
            throw new RuntimeException('无法读取应用私钥');
        }

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.system.oauth.token',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => (new \DateTime('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s'),
            'version' => '1.0',
            'grant_type' => 'authorization_code',
            'code' => $authCode,
        ];

        // 证书模式：必须携带 app_cert_sn 和 alipay_root_cert_sn
        if (is_file($appCertPath)) {
            $params['app_cert_sn'] = $this->getCertSN($appCertPath);
        }
        if (is_file($rootCertPath)) {
            $params['alipay_root_cert_sn'] = $this->getRootCertSN($rootCertPath);
        }

        // 签名：按 ASCII 升序拼接 key=value&（跳过空值）
        $signParams = $params;
        ksort($signParams);

        $signStr = '';
        foreach ($signParams as $k => $v) {
            if ($v === '') {
                continue;
            }
            $signStr .= $k.'='.$v.'&';
        }
        $signStr = rtrim($signStr, '&');

        $signature = '';
        if (! openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('支付宝 OAuth 签名失败');
        }
        $params['sign'] = base64_encode($signature);

        Log::debug('Alipay OAuth request', [
            'app_id' => $params['app_id'],
            'method' => $params['method'],
            'timestamp' => $params['timestamp'],
            'code_prefix' => substr($params['code'], 0, 4).'****'.substr($params['code'], -4),
            'has_app_cert_sn' => isset($params['app_cert_sn']),
            'has_root_cert_sn' => isset($params['alipay_root_cert_sn']),
        ]);

        $response = Http::asForm()->timeout(10)->post('https://openapi.alipay.com/gateway.do', $params);

        $rawBody = $response->body();
        // 清理 BOM 头和前导不可见字符
        $rawBody = preg_replace('/^\xEF\xBB\xBF/', '', $rawBody);
        $rawBody = ltrim($rawBody);
        // 支付宝网关错误响应可能返回 GBK 编码，转为 UTF-8 后再解析
        $encoding = mb_detect_encoding($rawBody, ['UTF-8', 'GBK', 'GB2312', 'ASCII'], true);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $rawBody = mb_convert_encoding($rawBody, 'UTF-8', $encoding);
        }

        $body = json_decode($rawBody, true);
        if (! is_array($body)) {
            throw new RuntimeException('支付宝 OAuth 响应解析失败：'.json_last_error_msg().'，原始响应：'.$rawBody);
        }

        // 网关层错误（签名错、缺证书 SN 等）
        if (isset($body['error_response'])) {
            $err = $body['error_response'];
            throw new RuntimeException(
                '支付宝 OAuth 网关错误：'.($err['sub_code'] ?? $err['code'] ?? 'unknown')
                .' - '.($err['sub_msg'] ?? $err['msg'] ?? '')
            );
        }

        // 业务层错误
        $tokenData = $body['alipay_system_oauth_token_response'] ?? [];
        if (isset($tokenData['code']) && $tokenData['code'] !== '10000') {
            throw new RuntimeException(
                '支付宝 OAuth 业务错误：'.($tokenData['sub_code'] ?? $tokenData['code'])
                .' - '.($tokenData['sub_msg'] ?? $tokenData['msg'] ?? '')
            );
        }

        return $tokenData;
    }

    /**
     * 计算应用公钥证书 SN（app_cert_sn）。
     */
    private function getCertSN(string $certPath): string
    {
        $cert = file_get_contents($certPath);
        if ($cert === false) {
            throw new RuntimeException('无法读取应用公钥证书');
        }

        $parsed = openssl_x509_parse($cert);
        if ($parsed === false) {
            throw new RuntimeException('解析应用公钥证书失败');
        }

        $issuerStr = $this->array2string(array_reverse($parsed['issuer'] ?? []));

        return md5($issuerStr.$this->formatSerialNumber($parsed));
    }

    /**
     * 计算支付宝根证书 SN（alipay_root_cert_sn）。
     * 根证书文件通常包含多个证书，各 SN 用下划线连接。
     */
    private function getRootCertSN(string $rootCertPath): string
    {
        $content = file_get_contents($rootCertPath);
        if ($content === false) {
            throw new RuntimeException('无法读取支付宝根证书');
        }

        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $content, $matches);
        if (empty($matches[0])) {
            throw new RuntimeException('根证书文件中未找到有效证书');
        }

        $sns = [];
        foreach ($matches[0] as $certPem) {
            $parsed = openssl_x509_parse($certPem);
            if ($parsed === false) {
                continue;
            }
            // 仅保留 RSA 签名类型的证书（排除 SM2、ecdsa 等）
            $sigType = $parsed['signatureTypeLN'] ?? '';
            if ($sigType !== 'sha1WithRSAEncryption' && $sigType !== 'sha256WithRSAEncryption') {
                continue;
            }
            $issuerStr = $this->array2string(array_reverse($parsed['issuer'] ?? []));
            $sns[] = md5($issuerStr.$this->formatSerialNumber($parsed));
        }

        return implode('_', $sns);
    }

    /**
     * 将数组转为 key=value 逗号分隔字符串（与 yansongda/pay SDK 一致）。
     *
     * @param  array<string, mixed>  $array
     */
    private function array2string(array $array): string
    {
        $parts = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $parts[] = $key.'='.$value;
        }

        return implode(',', $parts);
    }

    /**
     * 格式化证书序列号：0x 开头的十六进制转为十进制。
     *
     * @param  array<string, mixed>  $parsed
     */
    private function formatSerialNumber(array $parsed): string
    {
        $serial = (string) ($parsed['serialNumber'] ?? '');
        if (str_starts_with($serial, '0x')) {
            return $this->hex2dec($parsed['serialNumberHex'] ?? '');
        }

        return $serial;
    }

    /**
     * 十六进制转十进制字符串（处理大数）。
     */
    private function hex2dec(string $hex): string
    {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, (string) hexdec($hex[$i - 1]));
        }

        return $dec;
    }
}
