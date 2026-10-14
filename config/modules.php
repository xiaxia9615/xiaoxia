<?php

declare(strict_types=1);

return [
    'list' => [
        'User',
        'Product',
        'ApiKey',
        'Skill',
        'Ledger',
    ],

    'provider_pattern' => 'App\\Modules\\%s\\Providers\\%sServiceProvider',

    'dependencies' => [
        'User'    => ['Ledger'],
        'Product' => ['User'],
        'ApiKey'  => ['User', 'Product'],
        'Skill'   => ['User', 'Product', 'ApiKey', 'Ledger'],
        'Ledger'  => [],
    ],
];
