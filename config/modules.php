<?php

declare(strict_types=1);

return [
    'list' => [
        'User',
        'Product',
        'ApiKey',
        'Skill',
        'Ledger',
        'AiProvider',
        'Team',
        'Wallet',
        'Payment',
        'Order',
        'Distributor',
        'Address',
        'Cart',
        'Article',
        'Browse',
        'Comment',
        'Message',
        'System',
        'Favor',
        'Aftersale',
    ],

    'provider_pattern' => 'App\\Modules\\%s\\Providers\\%sServiceProvider',

    'dependencies' => [
        'User' => [],
        'Ledger' => ['User'],
        'Product' => ['User'],
        'ApiKey' => ['User', 'Product'],
        'AiProvider' => [],
        'Skill' => ['User', 'Product', 'ApiKey', 'Ledger', 'AiProvider', 'Wallet'],
        'Team' => ['User', 'Ledger', 'Wallet'],
        'System' => ['User', 'Product', 'ApiKey', 'Skill'],
        'Wallet' => ['User', 'Ledger', 'Payment'],
        'Payment' => [],
        'Order' => ['User', 'Product', 'Ledger', 'Distributor', 'Wallet', 'Cart'],
        'Distributor' => ['User'],
        'Address' => ['User'],
        'Cart' => ['User', 'Product'],
        'Goods' => ['Product'],
        'Article' => [],
        'Browse' => ['User', 'Product'],
        'Comment' => ['User', 'Product'],
        'Message' => ['User'],
        'Favor' => ['User', 'Product'],
        'Aftersale' => ['User', 'Order'],
    ],
];
