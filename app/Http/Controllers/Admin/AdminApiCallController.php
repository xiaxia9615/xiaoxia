<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminApiCallController extends Controller
{
    /**
     * API 调用流水列表（JSON，支持 user_id / product_id / start_date / end_date 筛选）.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = DB::table('api_calls')
            ->leftJoin('products', 'products.id', '=', 'api_calls.product_id')
            ->leftJoin('users', 'users.id', '=', 'api_calls.user_id')
            ->select(
                'api_calls.id',
                'api_calls.request_id',
                'api_calls.user_id',
                'users.name as user_name',
                'api_calls.product_id',
                'products.name as product_name',
                'api_calls.status_code',
                'api_calls.duration_ms',
                'api_calls.cost',
                'api_calls.created_at',
            );

        if ($userId = $request->query('user_id')) {
            $query->where('api_calls.user_id', (int) $userId);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('api_calls.product_id', (int) $productId);
        }

        if ($startDate = $request->query('start_date')) {
            $query->where('api_calls.created_at', '>=', $startDate.' 00:00:00');
        }

        if ($endDate = $request->query('end_date')) {
            $query->where('api_calls.created_at', '<=', $endDate.' 23:59:59');
        }

        $calls = $query->orderByDesc('api_calls.id')->paginate(30)->withQueryString();

        $list = collect($calls->items())->map(fn ($c): array => [
            'id' => $c->id,
            'request_id' => $c->request_id,
            'user_id' => $c->user_id,
            'user_name' => $c->user_name,
            'product_id' => $c->product_id,
            'product_name' => $c->product_name,
            'status_code' => (int) $c->status_code,
            'duration_ms' => (int) $c->duration_ms,
            'cost' => (int) $c->cost,
            'created_at' => (string) $c->created_at,
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $calls->total(),
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
            ],
        ]);
    }
}
