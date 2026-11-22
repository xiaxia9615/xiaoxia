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
        'app_secret_cert_path' => env('ALIPAY_APP_SECRET_CERT_PATH', storage_path('app/certs/app_private_key.pem')),
        'app_public_cert_path' => env('ALIPAY_APP_PUBLIC_CERT_PATH', storage_path('app/certs/app_cert.crt')),
        'alipay_public_cert_path' => env('ALIPAY_ALIPAY_PUBLIC_CERT_PATH', storage_path('app/certs/alipay_cert.crt')),
        'alipay_root_cert_path' => env('ALIPAY_ALIPAY_ROOT_CERT_PATH', storage_path('app/certs/alipay_root_cert.crt')),
        'notify_url' => env('ALIPAY_NOTIFY_URL'),
    ],
];
