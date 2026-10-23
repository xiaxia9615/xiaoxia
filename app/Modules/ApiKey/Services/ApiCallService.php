<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use App\Modules\ApiKey\Contracts\ApiCallContract;
use App\Modules\ApiKey\Models\ApiCall;

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
}
