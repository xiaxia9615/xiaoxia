<?php

declare(strict_types=1);

namespace App\Modules\Browse\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @group 浏览历史
 */
final class BrowseController
{
    public function index(): JsonResponse
    {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    }
}
