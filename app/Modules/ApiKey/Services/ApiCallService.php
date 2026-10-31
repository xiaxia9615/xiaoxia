<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use App\Modules\ApiKey\Contracts\ApiCallContract;
use App\Modules\ApiKey\Models\ApiCall;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ApiCallService implements ApiCallContract
{
    public function findByRequestId(?string $requestId): ?array
    {
        if (! $requestId) {
            return null;
        }
        $call = ApiCall::where('request_id', $requestId)->first();
        if (! $call) {
            return null;
        }

        return $call->toArray();
    }

    public function create(array $attributes): int
    {
        $call = ApiCall::create($attributes);

        return (int) $call->id;
    }

    public function listByUser(int $userId): array
    {
        return ApiCall::where('user_id', $userId)
            ->with('product:id,name')
            ->orderByDesc('id')
            ->paginate(20)->toArray();
    }

    public function countsByProduct(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        return ApiCall::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, COUNT(*) as cnt')
            ->groupBy('product_id')
            ->pluck('cnt', 'product_id')
            ->map(fn ($cnt) => (int) $cnt)
            ->all();
    }

    public function paginateCalls(int $perPage = 30, ?int $statusCode = null): LengthAwarePaginator
    {
        $query = ApiCall::query()
            ->select('id', 'request_id', 'user_id', 'product_id', 'status_code', 'duration_ms', 'cost', 'created_at')
            ->orderByDesc('id');
        if ($statusCode !== null) {
            $query->where('status_code', $statusCode);
        }
        return $query->paginate($perPage);
    }

    public function callStats(): array
    {
        return [
            'total'   => ApiCall::count(),
            'today'   => ApiCall::query()->whereDate('created_at', today())->count(),
            'success' => ApiCall::query()->where('status_code', 200)->count(),
        ];
    }
}
