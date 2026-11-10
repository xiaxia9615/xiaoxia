<?php

declare(strict_types=1);

namespace App\Modules\Cart\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 购物车管理
 */
final class CartController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    public function save(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    public function delete(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }
}
