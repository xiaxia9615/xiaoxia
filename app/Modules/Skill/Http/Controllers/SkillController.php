<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Actions\AuditSkillAction;
use App\Modules\Skill\Actions\CreateSkillAction;
use App\Modules\Skill\Actions\ListSkillCallsAction;
use App\Modules\Skill\Actions\SubmitSkillAction;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * 技能生命周期管理
 *
 * 技能创建、提交审核与管理员审核相关接口
 */
final class SkillController
{
    public function __construct(
        private readonly SkillProductContract $products,
        private readonly CreateSkillAction $createSkill,
        private readonly AuditSkillAction $auditSkill,
        private readonly SubmitSkillAction $submitSkill,
        private readonly ListSkillCallsAction $listSkillCalls,
    ) {}

    /**
     * 技能列表
     *
     * @group 技能市场
     *
     * @response {"code": 0, "data": {"data": [], "current_page": 1}}
     */
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'data' => $this->products->listSkills()]);
    }

    /**
     * 技能详情
     *
     * @group 技能市场
     *
     * @urlParam id integer required 技能 ID。Example: 1
     *
     * @response {"code": 0, "data": {"product": {}, "detail": {}}}
     * @response 404 {"code": 1, "message": "not found"}
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->products->findSkill($id);

        if (! $product) {
            return response()->json(['code' => 1, 'message' => 'not found'], 404);
        }

        $detail = SkillDetail::where('product_id', $id)->first();

        return response()->json(['code' => 0, 'data' => ['product' => $product, 'detail' => $detail]]);
    }

    /**
     * 创建技能草稿
     *
     * 认证开发者创建技能草稿，创建后状态为 draft。仅开发者可调用，非开发者返回 400。
     *
     * @authenticated
     *
     * @group 技能生命周期
     *
     * @bodyParam name string required 技能名称，最长 100 字符。Example: 天气查询技能
     * @bodyParam description string required 技能描述，最长 500 字符。Example: 查询指定城市天气
     * @bodyParam price integer required 价格（分），0 到 100000000。Example: 100
     * @bodyParam endpoint string required 调用地址，合法 URL。Example: https://api.example.com/weather
     * @bodyParam method string required 调用方法，GET 或 POST。Example: POST
     * @bodyParam api_key string optional 第三方 API Key。Example: sk-xxx
     * @bodyParam request_template object optional 请求模板。

     *
     * @response {"code": 0, "data": {"id": 1, "status": "draft"}}
     * @response 400 {"code": 1, "message": "仅认证开发者可创建技能"}
     */
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

    /**
     * 提交技能审核
     *
     * 开发者将技能从草稿（draft）提交审核，状态变更为 pending。仅技能所有者可操作，
     * 非所有者返回 403。
     *
     * @authenticated
     *
     * @group 技能生命周期
     *
     * @urlParam id integer required 技能 ID。Example: 1
     *
     * @response {"code": 0, "data": {"id": 1, "status": "pending"}}
     * @response 403 {"code": 1, "message": "无权操作他人技能"}
     * @response 400 {"code": 1, "message": "当前状态不允许提交审核"}
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->submitSkill->execute($id, (int) $request->user()->id);

            return response()->json(['code' => 0, 'data' => $result]);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '无权操作')) {
                return response()->json(['code' => 1, 'message' => $e->getMessage()], 403);
            }

            if (str_contains($e->getMessage(), 'not found')) {
                return response()->json(['code' => 1, 'message' => $e->getMessage()], 404);
            }

            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 技能调用记录
     *
     * @authenticated
     *
     * @group 技能市场
     *
     * @response {"code": 0, "data": []}
     */
    public function calls(Request $request): JsonResponse
    {
        $calls = $this->listSkillCalls->execute((int) $request->user()->id);

        return response()->json(['code' => 0, 'data' => $calls]);
    }

    /**
     * 审核技能
     *
     * 管理员审核待审核技能，通过后状态变更为 published，拒绝后变更为 rejected。仅管理员可调用。
     *
     * @authenticated
     *
     * @group 技能生命周期（管理员）
     *
     * @urlParam id integer required 技能 ID。Example: 1
     *
     * @bodyParam approve boolean required 是否通过审核。Example: true
     * @bodyParam note string optional 审核备注，最长 255 字符。Example: 内容合规
     *
     * @response {"code": 0, "data": {"id": 1, "status": 1, "skill_status": "published"}}
     * @response 404 {"code": 1, "message": "not found"}
     */
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
