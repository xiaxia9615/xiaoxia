<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

final class AlipayGateway implements PaymentGatewayInterface
{
    private const GATEWAY_URL = 'https://openapi.alipay.com/gateway.do';

    private const SIGN_TYPE = 'RSA2';

    private const CHARSET = 'UTF-8';

    private const PRODUCT_CODE = 'FACE_TO_FACE_PAYMENT';

    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    public function transfer(string $account, int $amountInCents): bool
    {
        throw new RuntimeException('AlipayGateway::transfer not implemented');
    }

    public function createPayment(string $orderNo, int $amountInCents, string $subject): array
    {
        $appId = config('payment.alipay.app_id');
        if (empty($appId)) {
            throw new RuntimeException('Alipay app_id is not configured');
        }

        $privateKeyPath = config('payment.alipay.private_key_path');
        if (empty($privateKeyPath) || ! file_exists($privateKeyPath)) {
            throw new RuntimeException('Alipay private key file not found: '.($privateKeyPath ?? '(null)'));
        }
        $privateKey = file_get_contents($privateKeyPath);
        if ($privateKey === false) {
            throw new RuntimeException('Failed to read Alipay private key file');
        }

        $notifyUrl = config('payment.alipay.notify_url');
        if (empty($notifyUrl)) {
            throw new RuntimeException('Alipay notify_url is not configured');
        }

        $totalAmount = number_format($amountInCents / 1000, 2, '.', '');

        $bizContent = json_encode([
            'out_trade_no' => $orderNo,
            'total_amount' => $totalAmount,
            'subject' => $subject,
            'product_code' => self::PRODUCT_CODE,
        ], JSON_UNESCAPED_UNICODE);

        if ($bizContent === false) {
            throw new RuntimeException('Failed to encode biz_content JSON');
        }

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.trade.precreate',
            'charset' => self::CHARSET,
            'sign_type' => self::SIGN_TYPE,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => $bizContent,
            'notify_url' => $notifyUrl,
        ];

        $signString = $this->buildSignString($params);
        $signature = $this->rsaSign($signString, $privateKey);
        if ($signature === false) {
            throw new RuntimeException('RSA2 signature generation failed');
        }
        $params['sign'] = $signature;

        try {
            $response = $this->httpClient->post(self::GATEWAY_URL, [
                'form_params' => $params,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Alipay API request failed: '.$e->getMessage(), 0, $e);
        }

        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);
        if (! is_array($result)) {
            throw new RuntimeException('Alipay API returned invalid JSON');
        }

        $precreateResult = $result['alipay_trade_precreate_response'] ?? null;
        if (! is_array($precreateResult)) {
            throw new RuntimeException('Alipay API response missing alipay_trade_precreate_response');
        }

        $code = $precreateResult['code'] ?? '';
        if ($code !== '10000') {
            $msg = $precreateResult['msg'] ?? '';
            $subMsg = $precreateResult['sub_msg'] ?? '';
            throw new RuntimeException(
                sprintf('Alipay precreate failed: code=%s msg=%s sub_msg=%s', $code, $msg, $subMsg)
            );
        }

        $qrCode = $precreateResult['qr_code'] ?? '';
        if (empty($qrCode)) {
            throw new RuntimeException('Alipay precreate response missing qr_code');
        }

        return [
            'qr_code' => $qrCode,
            'out_trade_no' => $orderNo,
        ];
    }

    public function verifyNotify(array $payload): array
    {
        $publicKeyPath = config('payment.alipay.public_key_path');
        if (empty($publicKeyPath) || ! file_exists($publicKeyPath)) {
            throw new RuntimeException('Alipay public key file not found: '.($publicKeyPath ?? '(null)'));
        }
        $publicKey = file_get_contents($publicKeyPath);
        if ($publicKey === false) {
            throw new RuntimeException('Failed to read Alipay public key file');
        }

        $sign = $payload['sign'] ?? '';
        if (empty($sign)) {
            return ['verified' => false, 'order_no' => '', 'amount_in_cents' => 0];
        }

        $signType = $payload['sign_type'] ?? self::SIGN_TYPE;

        $signParams = $payload;
        unset($signParams['sign'], $signParams['sign_type']);
        $signString = $this->buildSignString($signParams);

        $verified = $this->rsaVerify($signString, $sign, $publicKey, $signType);

        $orderNo = $payload['out_trade_no'] ?? '';
        $totalAmount = $payload['total_amount'] ?? '0';

        $amountInCents = (int) round((float) $totalAmount * 1000);

        return [
            'verified' => $verified,
            'order_no' => $orderNo,
            'amount_in_cents' => $amountInCents,
        ];
    }

    private function buildSignString(array $params): string
    {
        $params = array_filter($params, fn ($v) => $v !== '' && $v !== null);
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key.'='.$value;
        }

        return implode('&', $pairs);
    }

    private function rsaSign(string $data, string $privateKey): string|false
    {
        $keyResource = openssl_pkey_get_private($privateKey);
        if ($keyResource === false) {
            return false;
        }
        $signature = '';
        $result = openssl_sign($data, $signature, $keyResource, OPENSSL_ALGO_SHA256);

        return $result ? base64_encode($signature) : false;
    }

    private function rsaVerify(string $data, string $sign, string $publicKey, string $signType): bool
    {
        $keyResource = openssl_pkey_get_public($publicKey);
        if ($keyResource === false) {
            return false;
        }
        $decodedSign = base64_decode($sign, true);
        if ($decodedSign === false) {
            return false;
        }
        $algo = $signType === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $result = openssl_verify($data, $decodedSign, $keyResource, $algo);

        return $result === 1;
    }
}
