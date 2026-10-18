<?php
declare(strict_types=1);
namespace App\Modules\Skill\Actions;
use App\Modules\ApiKey\Contracts\ApiCallContract;

final class ListSkillCallsAction
{
    public function __construct(
        private readonly ApiCallContract $apiCalls,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $userId): array
    {
        return $this->apiCalls->listByUser($userId);
    }
}
