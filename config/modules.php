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
        'Order' => ['User', 'Product', 'Ledger', 'Distributor', 'Wallet'],
        'Distributor' => ['User'],
        'Address' => ['User'],
        'Cart' => ['User', 'Product'],
        'Article' => [],
        'Browse' => ['User', 'Product'],
        'Comment' => ['User', 'Product'],
        'Message' => ['User'],
    ],
];
