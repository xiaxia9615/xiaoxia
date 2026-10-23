<?php

declare(strict_types=1);

return [
    'gateway_per_minute' => (int) env('RATELIMIT_GATEWAY_PER_MINUTE', 60),
    'login_per_minute'   => (int) env('RATELIMIT_LOGIN_PER_MINUTE', 10),
];
