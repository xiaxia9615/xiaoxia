<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Contracts;

interface ApiKeyContract
{
    /**
     * 查询用户的有效 ApiKey，不存在返回 null
     *
     * @return array{id: int, user_id: int}|null
     */
    public function findActiveKeyForUser(int $userId): ?array;
}
