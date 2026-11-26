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

    /** 场景 -> 模板参数数量映射。 */
    private const SCENE_PARAMS = [
        'register' => 2,      // {1}验证码, {2}有效时长
        'login' => 1,         // {1}验证码
        'bind_phone' => 2,    // {1}验证码, {2}有效时长
        'change_phone' => 1,  // {1}验证码
    ];

    public function __construct()
    {
        $this->easySms = new EasySms(config('easysms'));
    }

    /**
     * 发送短信验证码到指定手机号。
     *
     * @param string $scene 场景：register / login / bind_phone / change_phone
     *
     * @throws RuntimeException
     */
    public function sendCode(string $phone, string $code, string $scene = 'register'): void
    {
        $templates = config('easysms.gateways.qcloud.templates', []);
        $templateId = (string) ($templates[$scene] ?? config('easysms.gateways.qcloud.template_id', ''));

        $paramCount = self::SCENE_PARAMS[$scene] ?? 2;
        $data = $paramCount === 2 ? [$code, '5'] : [$code];

        try {
            $this->easySms->send($phone, [
                'template' => $templateId,
                'data' => $data,
            ]);
        } catch (EasySmsException $e) {
            Log::error('SMS send failed: '.$e->getMessage(), [
                'phone' => $phone,
                'scene' => $scene,
                'exception' => $e,
            ]);

            throw new RuntimeException('短信发送失败，请稍后重试');
        }
    }
}
