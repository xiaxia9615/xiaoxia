<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use App\Modules\ApiKey\Contracts\ApiKeyContract;
use App\Modules\ApiKey\Models\ApiKey;

final class ApiKeyQueryService implements ApiKeyContract
{
    public function findActiveKeyForUser(int $userId): ?array
    {
        $key = ApiKey::where('user_id', $userId)
            ->where('status', 1)
            ->first();
        if (! $key) {
            return null;
        }

        return ['id' => (int) $key->id, 'user_id' => (int) $key->user_id];
    }
}
