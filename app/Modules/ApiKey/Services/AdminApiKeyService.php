<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AdminApiKeyService
{
    public function paginateKeys(int $perPage = 20)
    {
        return DB::table('api_keys as ak')
            ->leftJoin('users as u', 'u.id', '=', 'ak.user_id')
            ->select(
                'ak.id',
                'ak.user_id',
                'ak.name',
                'ak.key_prefix',
                'ak.status',
                'ak.last_used_at',
                'ak.expires_at',
                'ak.created_at',
                'u.email as user_email',
            )
            ->orderByDesc('ak.id')
            ->paginate($perPage);
    }

    public function revoke(int $id): bool
    {
        $affected = DB::table('api_keys')
            ->where('id', $id)
            ->where('status', '!=', 0)
            ->update(['status' => 0, 'updated_at' => now()]);

        if ($affected > 0) {
            Log::info('admin revoked api key', [
                'key_id'     => $id,
                'by_user_id' => auth()->id(),
            ]);
        }

        return $affected > 0;
    }

    public function stats(): array
    {
        return [
            'total'   => DB::table('api_keys')->count(),
            'active'  => DB::table('api_keys')->where('status', 1)->count(),
            'revoked' => DB::table('api_keys')->where('status', 0)->count(),
            'expired' => DB::table('api_keys')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
        ];
    }
}
