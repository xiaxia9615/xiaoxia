<?php

declare(strict_types=1);

return [
    'default' => env('PAYMENT_GATEWAY', 'mock'),
    'wechat' => [
        'app_id' => env('WECHAT_PAY_APP_ID'),
        'mch_id' => env('WECHAT_PAY_MCH_ID'),
        'key' => env('WECHAT_PAY_KEY'),
        'notify_url' => env('WECHAT_PAY_NOTIFY_URL'),
    ],
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID'),
        'private_key' => env('ALIPAY_PRIVATE_KEY'),
        'public_key' => env('ALIPAY_PUBLIC_KEY'),
        'notify_url' => env('ALIPAY_NOTIFY_URL'),
    ],
];
