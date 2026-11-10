<?php

declare(strict_types=1);

namespace App\Modules\Message\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 消息管理
 */
final class MessageController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }

    public function unread(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['count' => 0]]);
    }

    public function read(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }
}
