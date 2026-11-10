<?php
declare(strict_types=1);

namespace App\Modules\Favor\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 收藏管理
 */
final class FavorController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    public function status(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['is_favor' => false]]);
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
