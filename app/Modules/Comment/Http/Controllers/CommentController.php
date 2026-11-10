<?php
declare(strict_types=1);

namespace App\Modules\Comment\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 评论管理
 */
final class CommentController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }
}
