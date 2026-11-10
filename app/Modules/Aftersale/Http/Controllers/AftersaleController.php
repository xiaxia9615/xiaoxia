<?php
declare(strict_types=1);

namespace App\Modules\Aftersale\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 售后管理
 */
final class AftersaleController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    public function save(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    public function cancel(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }
}
