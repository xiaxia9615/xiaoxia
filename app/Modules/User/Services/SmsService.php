<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\Exception as EasySmsException;
use RuntimeException;

final class SmsService
{
    private EasySms $easySms;

    public function __construct()
    {
        $this->easySms = new EasySms(config('easysms'));
    }

    /**
     * 发送短信验证码到指定手机号。
     *
     * @throws RuntimeException
     */
    public function sendCode(string $phone, string $code): void
    {
        $templateId = (string) config('easysms.gateways.qcloud.template_id', '');

        try {
            $this->easySms->send($phone, [
                'template' => $templateId,
                'data' => [$code, '5'],
            ]);
        } catch (EasySmsException $e) {
            Log::error('SMS send failed: '.$e->getMessage(), [
                'phone' => $phone,
                'exception' => $e,
            ]);

            throw new RuntimeException('短信发送失败，请稍后重试');
        }
    }
}
