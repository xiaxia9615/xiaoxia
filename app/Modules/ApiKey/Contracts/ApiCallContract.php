<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    /**
     * 按产品 ID 批量统计调用次数
     *
     * @param  array<int, int>  $productIds
     * @return array<int, int>  product_id => 调用次数（无记录的产品不出现在结果中）
     */
    public function countsByProduct(array $productIds): array;

    /**
     * 调用记录分页（可按 HTTP 状态码过滤，默认按 ID 倒序）
     */
    public function paginateCalls(int $perPage = 30, ?int $statusCode = null): LengthAwarePaginator;

    /**
     * 调用统计：总数 / 今日调用 / 成功调用（status_code = 200）
     *
     * @return array{total: int, today: int, success: int}
     */
    public function callStats(): array;
}
