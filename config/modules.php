<?php

declare(strict_types=1);

return [
    'list' => [
        'User',
        'Product',
        'ApiKey',
        'Skill',
    ],

    'provider_pattern' => 'App\\Modules\\%s\\Providers\\%sServiceProvider',

    'dependencies' => [
        'User'    => [],
        'Product' => ['User'],
        'ApiKey'  => ['User', 'Product'],
        'Skill'   => ['Product'],
    ],
];
