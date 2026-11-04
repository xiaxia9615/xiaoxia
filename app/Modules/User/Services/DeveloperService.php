<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Enums\DeveloperStatus;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class DeveloperService
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    /**
     * 用户申请成为开发者（状态变更为 PENDING）
     *
     * @param array{reason?: string} $data
     * @return array{user_id: int, developer_status: string}
     */
    public function apply(User $user, array $data): array
    {
        $status = $this->resolveStatus($user->developer_status);

        if (! $status->canApply()) {
            throw new RuntimeException('当前状态不允许提交开发者申请');
        }

        $user->update([
            'developer_status' => DeveloperStatus::Pending->value,
        ]);

        return [
            'user_id' => (int) $user->id,
            'developer_status' => DeveloperStatus::Pending->value,
        ];
    }

    /**
     * 管理员审核开发者申请（通过后 is_developer=true 且状态变更为 APPROVED）
     *
     * @return array{user_id: int, is_developer: bool, developer_status: string}
     */
    public function audit(int $userId, bool $approve): array
    {
        /** @var User|null $user */
        $user = User::query()->find($userId);

        if ($user === null) {
            throw new RuntimeException("User not found: {$userId}");
        }

        $status = $this->resolveStatus($user->developer_status);

        if ($status !== DeveloperStatus::Pending) {
            throw new RuntimeException('该用户当前没有待审核的开发者申请');
        }

        $user->update([
            'is_developer' => $approve,
            'developer_status' => $approve ? DeveloperStatus::Approved->value : DeveloperStatus::Rejected->value,
        ]);

        return [
            'user_id' => (int) $user->id,
            'is_developer' => (bool) $user->is_developer,
            'developer_status' => $this->resolveStatus($user->developer_status)->value,
        ];
    }

    /**
     * 管理员分页查询待审核的开发者列表
     */
    public function paginatePendingDevelopers(int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->where('developer_status', DeveloperStatus::Pending)
            ->orderBy('id')
            ->paginate($perPage);
    }

    private function resolveStatus(DeveloperStatus|string $status): DeveloperStatus
    {
        return $status instanceof DeveloperStatus ? $status : DeveloperStatus::from($status);
    }
}
