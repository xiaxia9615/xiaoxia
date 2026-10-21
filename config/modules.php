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
    ],

    'provider_pattern' => 'App\\Modules\\%s\\Providers\\%sServiceProvider',

    'dependencies' => [
        'User'       => [],
        'Ledger'     => ['User'],
        'Product'    => ['User'],
        'ApiKey'     => ['User', 'Product'],
        'AiProvider' => [],
        'Skill'      => ['User', 'Product', 'ApiKey', 'Ledger', 'AiProvider'],
        'Team'       => ['User'],
    ],
];
