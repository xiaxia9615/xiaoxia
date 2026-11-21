<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ApiKey\Services\AdminApiKeyService;
use App\Modules\System\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminApiKeyController extends Controller
{
    public function __construct(
        private readonly AdminApiKeyService $service,
        private readonly AuditService $auditService,
    ) {}

    /**
     * API Key 列表（JSON，key 脱敏输出：仅 key_prefix + ****）.
     */
    public function apiIndex(): JsonResponse
    {
        $keys = $this->service->paginateKeys();

        $list = collect($keys->items())->map(fn ($k): array => [
            'id' => $k->id,
            'user_id' => $k->user_id,
            'user_email' => $k->user_email,
            'name' => $k->name,
            'masked_key' => ($k->key_prefix ?? '').'****',
            'status' => (int) $k->status,
            'last_used_at' => $k->last_used_at,
            'expires_at' => $k->expires_at,
            'created_at' => $k->created_at,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $keys->total(),
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
                'per_page' => $keys->perPage(),
            ],
        ]);
    }

    /**
     * API 吊销 Key（JSON）.
     */
    public function apiRevoke(Request $request, int $id): JsonResponse
    {
        $ok = $this->service->revoke($id);

        if (! $ok) {
            return response()->json([
                'code' => 1,
                'msg' => '吊销失败（不存在或已吊销）',
                'data' => null,
            ], 422);
        }

        $this->auditService->log(
            (int) $request->user()->id,
            'admin.apikey.revoke',
            'api_key',
            $id,
        );

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['id' => $id, 'status' => 0],
        ]);
    }
}
