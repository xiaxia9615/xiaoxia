<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Modules\User\Services\DeveloperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * 开发者生态入驻
 *
 * 开发者申请入驻与管理员审核相关接口
 */
final class DeveloperController
{
    public function __construct(
        private readonly DeveloperService $developers,
    ) {}

    /**
     * 申请成为开发者
     *
     * 已登录用户提交开发者入驻申请，申请后状态变更为 pending，等待管理员审核。
     * 重复申请（pending/approved 状态）将返回 400。
     *
     * @authenticated
     *
     * @group 开发者入驻
     *
     * @bodyParam reason string optional 申请理由，最长 500 字符。Example: 希望上架天气查询技能
     *
     * @response {"code": 0, "data": {"user_id": 1, "developer_status": "pending"}}
     * @response 400 {"code": 1, "message": "当前状态不允许提交开发者申请"}
     */
    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->developers->apply($request->user(), $data);

            return response()->json(['code' => 0, 'data' => $result]);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 审核开发者申请
     *
     * 管理员审核开发者入驻申请。审核通过后 is_developer=true 且状态变更为 approved；
     * 拒绝后状态变更为 rejected。仅管理员可调用。
     *
     * @authenticated
     *
     * @group 开发者入驻（管理员）
     *
     * @urlParam id integer required 用户 ID。Example: 1
     *
     * @bodyParam approve boolean required 是否通过审核。Example: true
     *
     * @response {"code": 0, "data": {"user_id": 1, "is_developer": true, "developer_status": "approved"}}
     * @response 404 {"code": 1, "message": "User not found: 1"}
     */
    public function audit(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'approve' => 'required|boolean',
        ]);

        try {
            $result = $this->developers->audit($id, (bool) $data['approve']);

            return response()->json(['code' => 0, 'data' => $result]);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 404);
        }
    }
}
