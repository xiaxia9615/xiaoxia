<?php

declare(strict_types=1);

namespace App\Modules\Distributor\Http\Controllers;

use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DistributorController
{
    /**
     * 绑定上级达人（一个用户仅可绑定一次，重复绑定返回 400）
     *
     * @group 分销管理
     * @authenticated
     * @bodyParam distributor_id int required 达人用户ID Example: 2
     */
    public function bind(Request $request): JsonResponse
    {
        $data = $request->validate([
            'distributor_id' => 'required|integer|min:1',
        ]);

        $userId = (int) $request->user()->id;
        $distributorId = (int) $data['distributor_id'];

        if ($distributorId === $userId) {
            return response()->json(['code' => 1, 'message' => '不能绑定自己为上级达人'], 400);
        }

        $distributor = User::query()->find($distributorId);
        if (! $distributor) {
            return response()->json(['code' => 1, 'message' => '达人不存在'], 400);
        }

        // 一个用户仅可绑定一次：已存在绑定关系时严格拒绝
        if (DistributorRelation::query()->where('user_id', $userId)->exists()) {
            return response()->json(['code' => 1, 'message' => '已绑定达人，不可重复绑定'], 400);
        }

        try {
            $relation = DistributorRelation::create([
                'user_id' => $userId,
                'distributor_id' => $distributorId,
            ]);
        } catch (QueryException $e) {
            // 23000: 唯一键冲突（并发下重复绑定），同样拒绝
            if ($e->getCode() === '23000') {
                return response()->json(['code' => 1, 'message' => '已绑定达人，不可重复绑定'], 400);
            }

            throw $e;
        }

        return response()->json(['code' => 0, 'data' => $relation, 'message' => '绑定成功']);
    }

    /**
     * 查询我的上级达人
     *
     * @group 分销管理
     * @authenticated
     */
    public function show(Request $request): JsonResponse
    {
        $relation = DistributorRelation::query()
            ->with('distributor:id,name')
            ->where('user_id', (int) $request->user()->id)
            ->first();

        return response()->json(['code' => 0, 'data' => $relation]);
    }
}
