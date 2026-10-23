<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Actions\AuditSkillAction;
use App\Modules\Skill\Actions\CreateSkillAction;
use App\Modules\Skill\Actions\ListSkillCallsAction;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class SkillController
{
    public function __construct(
        private readonly SkillProductContract $products,
        private readonly CreateSkillAction $createSkill,
        private readonly AuditSkillAction $auditSkill,
        private readonly ListSkillCallsAction $listSkillCalls,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'data' => $this->products->listSkills()]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->products->findSkill($id);
        if (! $product) {
            return response()->json(['code' => 1, 'message' => 'not found'], 404);
        }
        $detail = SkillDetail::where('product_id', $id)->first();

        return response()->json(['code' => 0, 'data' => ['product' => $product, 'detail' => $detail]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'price' => 'required|integer|min:0|max:100000000',
            'endpoint' => 'required|url|max:255',
            'method' => 'required|in:GET,POST',
            'api_key' => 'nullable|string|max:500',
            'request_template' => 'nullable|array',
        ]);
        try {
            $result = $this->createSkill->execute($request->user(), $data);

            return response()->json(['code' => 0, 'data' => $result]);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            \Log::error('skill store failed', ['error' => $e->getMessage()]);

            return response()->json(['code' => 1, 'message' => '系统繁忙，请稍后重试'], 500);
        }
    }

    public function calls(Request $request): JsonResponse
    {
        $calls = $this->listSkillCalls->execute((int) $request->user()->id);

        return response()->json(['code' => 0, 'data' => $calls]);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['code' => 1, 'message' => '无权限'], 403);
        }
        $data = $request->validate([
            'approve' => 'required|boolean',
            'note' => 'nullable|string|max:255',
        ]);
        try {
            $result = $this->auditSkill->execute($id, (bool) $data['approve'], $data['note'] ?? null);

            return response()->json(['code' => 0, 'data' => $result]);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => 'not found'], 404);
        }
    }
}
