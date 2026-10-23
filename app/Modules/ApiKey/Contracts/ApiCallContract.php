<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Contracts;

interface ApiCallContract
{
    /**
     * 按 request_id 查询调用记录（幂等），不存在返回 null
     *
     * @return array<string, mixed>|null
     */
    public function findByRequestId(?string $requestId): ?array;

    /**
     * 创建调用记录，返回记录 ID
     */
    public function create(array $attributes): int;

    /**
     * 用户调用记录分页列表（paginator toArray 结构）
     *
     * @return array<string, mixed>
     */
    public function listByUser(int $userId): array;
}
