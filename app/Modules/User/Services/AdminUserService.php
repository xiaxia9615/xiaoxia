<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function paginateUsers(int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findUser(int $id): User
    {
        return User::query()
            ->with('roles')
            ->withCount(['apiKeys as api_keys_count'])
            ->where('id', $id)
            ->firstOrFail();
    }


    /**
     * 修改角色. 写操作.
     *
     * @throws \InvalidArgumentException
     */
    public function updateRole(int $operatorId, int $userId, string $role): void
    {
        if (! in_array($role, ['admin', 'user'], true)) {
            throw new \InvalidArgumentException('无效角色');
        }

        if ($operatorId === $userId && $role !== 'admin') {
            throw new \InvalidArgumentException('不能移除自己的 admin 角色');
        }

        $user = User::query()->where('id', $userId)->firstOrFail();
        $user->syncRoles([$role]);
    }

    /**
     * 启用/禁用. 写操作.
     *
     * @throws \InvalidArgumentException
     */
    public function toggleActive(int $operatorId, int $userId): bool
    {
        if ($operatorId === $userId) {
            throw new \InvalidArgumentException('不能禁用自己');
        }

        $user = User::query()->where('id', $userId)->firstOrFail();
        $user->is_active = ! $user->is_active;
        $user->save();

        return (bool) $user->is_active;
    }
}
