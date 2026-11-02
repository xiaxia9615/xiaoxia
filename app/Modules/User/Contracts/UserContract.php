<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts;

interface UserContract
{
    /**
     * 判断用户是否为已认证开发者
     */
    public function isDeveloper(int $userId): bool;
}
