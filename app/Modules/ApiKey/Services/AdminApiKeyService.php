<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use Illuminate\Support\Facades\Log;

final class AdminApiKeyService
{
    public function __construct(
        private readonly AdminApiKeyQueryService $query,
    ) {}

    public function paginateKeys(int $perPage = 20)
    {
        return $this->query->paginateKeys($perPage);
    }

    public function revoke(int $id): bool
    {
        $affected = $this->query->revokeById($id);

        if ($affected) {
            Log::info('admin revoked api key', [
                'key_id'     => $id,
                'by_user_id' => auth()->id(),
            ]);
        }

        return $affected;
    }

    public function stats(): array
    {
        return $this->query->stats();
    }
}
