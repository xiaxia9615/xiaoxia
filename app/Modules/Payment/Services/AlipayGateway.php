<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Alipay;

/**
 * 支付宝网关（公钥证书模式，基于 yansongda/pay v3）。
 *
 * 金额单位约定：内部为毫（千分之一元），与支付宝交互时转为元（两位小数）。
 */
final class AlipayGateway implements PaymentGatewayInterface
{
    public function transfer(string $account, int $amountInCents): bool
    {
        $result = $this->payClient()->transfer([
            'out_biz_no' => 'transfer_'.time().'_'.random_int(1000, 9999),
            'trans_amount' => number_format($amountInCents / 1000, 2, '.', ''),
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER',
            'payee_info' => [
                'identity' => $account,
                'identity_type' => 'ALIPAY_LOGON_ID',
            ],
        ]);

        $status = $result->get('status', '');
        $code = $result->get('code', '');

        if ($code === '10000' && in_array($status, ['SUCCESS', 'SUCCESS_WITHDRAWING'], true)) {
            return true;
        }

        Log::warning('AlipayGateway transfer failed', [
            'account' => $account,
            'amount_in_cents' => $amountInCents,
            'status' => $status,
            'code' => $code,
            'msg' => $result->get('msg', ''),
            'sub_code' => $result->get('sub_code', ''),
            'sub_msg' => $result->get('sub_msg', ''),
        ]);

        return false;
    }

    public function createPayment(string $orderNo, int $amountInCents, string $subject): array
    {
        $result = $this->payClient()->scan([
            'out_trade_no' => $orderNo,
            'total_amount' => number_format($amountInCents / 1000, 2, '.', ''),
            'subject' => $subject,
        ]);

        $qrCode = $result->get('qr_code', '');
        if (! is_string($qrCode) || $qrCode === '') {
            throw new RuntimeException('Alipay scan response missing qr_code');
        }

        return [
            'qr_code' => $qrCode,
            'out_trade_no' => $orderNo,
        ];
    }

    public function verifyNotify(array $payload): array
    {
        try {
            $collection = $this->payClient()->callback($payload);
        } catch (Throwable) {
            return ['verified' => false, 'order_no' => '', 'amount_in_cents' => 0];
        }

        $orderNo = $collection->get('out_trade_no', '');
        $totalAmount = $collection->get('total_amount', '0');

        return [
            'verified' => true,
            'order_no' => is_string($orderNo) ? $orderNo : '',
            'amount_in_cents' => (int) round(((float) (is_string($totalAmount) ? $totalAmount : '0')) * 1000),
        ];
    }

    /**
     * 构建 SDK 客户端：校验 4 个证书路径存在后注册配置。
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
