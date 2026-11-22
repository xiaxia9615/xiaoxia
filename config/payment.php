<?php

declare(strict_types=1);

return [
    'default' => env('PAYMENT_GATEWAY', 'mock'),
    'allow_mock' => env('ALLOW_MOCK_PAYMENT', false),
    'wechat' => [
        'app_id' => env('WECHAT_PAY_APP_ID'),
        'mch_id' => env('WECHAT_PAY_MCH_ID'),
        'key' => env('WECHAT_PAY_KEY'),
        'notify_url' => env('WECHAT_PAY_NOTIFY_URL'),
    ],
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID'),
        'private_key_path' => env('ALIPAY_PRIVATE_KEY_PATH', storage_path('app/certs/alipay_private_key.pem')),
        'public_key_path' => env('ALIPAY_PUBLIC_KEY_PATH', storage_path('app/certs/alipay_public_key.pem')),
        'notify_url' => env('ALIPAY_NOTIFY_URL'),
    ],
];
