<?php
declare(strict_types=1);

namespace App\Modules\Goods\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 商品管理
 */
final class GoodsController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    public function category(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    public function detail(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }
}
