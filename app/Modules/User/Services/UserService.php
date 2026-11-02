<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Contracts\UserContract;
use App\Modules\User\Models\User;

class UserService implements UserContract
{
    public function isDeveloper(int $userId): bool
    {
        $user = User::query()->find($userId);

        return $user !== null && (bool) $user->is_developer;
    }
}
