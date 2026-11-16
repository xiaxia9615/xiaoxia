<?php

declare(strict_types=1);
use Overtrue\EasySms\Strategies\OrderStrategy;

return [
    'timeout' => 5.0,

    'default' => [
        'strategy' => OrderStrategy::class,
        'gateways' => ['qcloud'],
    ],

    'gateways' => [
        'errorlog' => [
            'file' => storage_path('logs/easy-sms.log'),
        ],
        'qcloud' => [
            'sdk_app_id' => env('QCLOUD_SMS_SDK_APP_ID', ''),
            'secret_id' => env('QCLOUD_SMS_SECRET_ID', ''),
            'secret_key' => env('QCLOUD_SMS_SECRET_KEY', ''),
            'sign_name' => env('QCLOUD_SMS_SIGN_NAME', ''),
        ],
    ],
];
