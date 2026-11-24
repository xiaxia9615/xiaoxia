<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', 'https://xiaoxia.ren/api/v1/auth/callback/github'),
    ],

    'weixin' => [
        'client_id' => env('WEIXIN_CLIENT_ID'),
        'client_secret' => env('WEIXIN_CLIENT_SECRET'),
        'redirect' => env('WEIXIN_REDIRECT_URI', 'https://xiaoxia.ren/api/v1/auth/callback/weixin'),
    ],
    'weixin_mini' => [
        'appid' => env('WEIXIN_MINI_APP_ID'),
        'secret' => env('WEIXIN_MINI_APP_SECRET'),
    ],

    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID'),
        'private_key' => env('ALIPAY_PRIVATE_KEY'),
        'public_key' => env('ALIPAY_PUBLIC_KEY'),
        'redirect' => env('ALIPAY_REDIRECT_URI', 'https://xiaoxia.ren/api/v1/auth/callback/alipay'),
    ],

];
