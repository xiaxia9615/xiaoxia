<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Skill\Services\AdminSkillService;
use App\Modules\System\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminSkillController extends Controller
{
    public function __construct(
        private readonly AdminSkillService $service,
    ) {}

    /**
     * API 技能列表（JSON，api_key 脱敏输出）.
     */
    public function apiIndex(): JsonResponse
    {
        $skills = $this->service->paginateSkills();

        $list = collect($skills->items())->map(fn ($s): array => [
            'id' => $s->id,
            'product_id' => $s->product_id,
            'product_name' => $s->product_name,
            'endpoint' => $s->endpoint,
            'method' => $s->method,
            'pricing_type' => (int) $s->pricing_type,
            'masked_api_key' => $s->masked_api_key,
            'free_quota' => $s->free_quota,
            'product_status' => (int) $s->product_status,
            'calls_count' => $s->calls_count,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $skills->total(),
                'current_page' => $skills->currentPage(),
                'last_page' => $skills->lastPage(),
                'per_page' => $skills->perPage(),
            ],
        ]);
    }

    /**
     * API 待审核技能列表（JSON）.
     */
    public function apiPendingIndex(): JsonResponse
    {
        $skills = $this->service->paginatePendingSkills(20);
        $list = collect($skills->items())->map(fn ($s): array => [
            'id' => $s->id,
            'product_id' => $s->product_id,
            'product_name' => $s->product?->name,
            'endpoint' => $s->endpoint,
            'method' => $s->method,
            'pricing_type' => (int) $s->pricing_type,
            'status' => $s->status instanceof \App\Modules\Skill\Enums\SkillStatus
                ? $s->status->value
                : (string) $s->status,
            'system_prompt' => $s->system_prompt,
            'free_quota' => $s->free_quota,
            'created_at' => $s->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $skills->total(),
                'current_page' => $skills->currentPage(),
                'last_page' => $skills->lastPage(),
                'per_page' => $skills->perPage(),
            ],
        ]);
    }

    /**
     * API 切换技能上下架状态（JSON）.
     */
    public function apiToggleStatus(Request $request, AuditService $auditService, int $id): JsonResponse
    {
        $product = $this->service->toggleStatus($id);

        $auditService->log(
            (int) $request->user()->id,
            'admin.skill.toggle_status',
            'product',
            $product->id,
            ['name' => $product->name, 'status' => $product->status],
        );

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'id' => $id,
                'product_id' => $product->id,
                'status' => $product->status,
            ],
        ]);
    }
}
