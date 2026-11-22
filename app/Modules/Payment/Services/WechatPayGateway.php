<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Wechat;

/**
 * 微信支付网关（V3 API，基于 yansongda/pay v3）。
 *
 * 金额单位约定：内部为分（cents），与微信交互时直接使用分（整数）。
 */
final class WechatPayGateway implements PaymentGatewayInterface
{
    public function transfer(string $account, int $amountInCents): bool
    {
        $result = $this->payClient()->transfer([
            'out_batch_no' => 'transfer_'.time().'_'.random_int(1000, 9999),
            'batch_name' => '提现转账',
            'batch_remark' => '用户提现',
            'total_amount' => $amountInCents,
            'total_num' => 1,
            'transfer_detail_list' => [
                [
                    'out_detail_no' => 'detail_'.time().'_'.random_int(1000, 9999),
                    'transfer_amount' => $amountInCents,
                    'transfer_remark' => '提现',
                    'openid' => $account,
                ],
            ],
        ]);

        $batchId = $result->get('batch_id', '');
        if (is_string($batchId) && $batchId !== '') {
            return true;
        }

        Log::warning('WechatPayGateway transfer failed', [
            'account' => $account,
            'amount_in_cents' => $amountInCents,
            'result' => $result->all(),
        ]);

        return false;
    }

    public function createPayment(string $orderNo, int $amountInCents, string $subject): array
    {
        $result = $this->payClient()->scan([
            'out_trade_no' => $orderNo,
            'description' => $subject,
            'amount' => [
                'total' => $amountInCents,
                'currency' => 'CNY',
            ],
        ]);

        $codeUrl = $result->get('code_url', '');
        if (! is_string($codeUrl) || $codeUrl === '') {
            throw new RuntimeException('Wechat scan response missing code_url');
        }

        return [
            'qr_code' => $codeUrl,
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
        $amount = $collection->get('amount', []);
        $amountInCents = is_array($amount) ? (int) ($amount['total'] ?? 0) : 0;

        return [
            'verified' => true,
            'order_no' => is_string($orderNo) ? $orderNo : '',
            'amount_in_cents' => $amountInCents,
        ];
    }

    /**
     * 构建 SDK 客户端：校验必需配置后注册。
     *
     * @throws RuntimeException 配置缺失
     */
    private function payClient(): Wechat
    {
        $appId = config('payment.wechat.app_id');
        $mchId = config('payment.wechat.mch_id');
        $mchSecretCert = config('payment.wechat.mch_secret_cert');
        $mchSecretKey = config('payment.wechat.mch_secret_key');

        if (! is_string($appId) || $appId === '') {
            throw new RuntimeException('Wechat app_id is not configured');
        }

        if (! is_string($mchId) || $mchId === '') {
            throw new RuntimeException('Wechat mch_id is not configured');
        }

        if (! is_string($mchSecretCert) || $mchSecretCert === '') {
            throw new RuntimeException('Wechat mch_secret_cert is not configured');
        }

        if (! is_string($mchSecretKey) || $mchSecretKey === '') {
            throw new RuntimeException('Wechat mch_secret_key is not configured');
        }

        $mchPublicCertPath = config('payment.wechat.mch_public_cert_path');
        $config = [
            'app_id' => $appId,
            'mch_id' => $mchId,
            'mch_secret_cert' => $mchSecretCert,
            'mch_secret_key' => $mchSecretKey,
            'notify_url' => config('payment.wechat.notify_url'),
        ];

        if (is_string($mchPublicCertPath) && $mchPublicCertPath !== '' && file_exists($mchPublicCertPath)) {
            $config['mch_public_cert_path'] = $mchPublicCertPath;
        }

        Pay::config([
            'wechat' => [
                'default' => $config,
            ],
        ]);

        return Pay::wechat();
    }
}
