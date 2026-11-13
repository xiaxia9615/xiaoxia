<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminApiCallController extends Controller
{
    public function index(Request $request): View
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

        $rows = $calls->getCollection()->map(fn ($c): array => [
            e((string) $c->created_at),
            e((string) $c->request_id),
            $c->user_id.' ('.e((string) ($c->user_name ?? '-')).')',
            $c->product_id.' ('.e((string) ($c->product_name ?? '-')).')',
            $c->status_code === 200
                ? '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-sm text-slate-600">'.$c->status_code.'</span></span>'
                : '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span><span class="text-sm text-slate-600">'.$c->status_code.'</span></span>',
            $c->duration_ms.'ms',
            number_format($c->cost / 100, 4),
            '<a href="'.route('admin.api-calls.ledger', $c->id).'" class="text-indigo-600 hover:text-indigo-800">账本核对</a>',
        ])->all();

        return view('admin.api-calls.index', [
            'calls' => $calls,
            'rows' => $rows,
            'filters' => [
                'user_id' => $request->query('user_id'),
                'product_id' => $request->query('product_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ],
        ]);
    }

    public function ledger(int $id): View
    {
        $apiCall = DB::table('api_calls')
            ->leftJoin('products', 'products.id', '=', 'api_calls.product_id')
            ->select('api_calls.*', 'products.name as product_name')
            ->where('api_calls.id', $id)
            ->first();

        if (! $apiCall) {
            abort(404);
        }

        $entries = DB::table('ledger_entries')
            ->where('biz_type', 'api_call')
            ->where('biz_id', $id)
            ->orderByDesc('id')
            ->get();

        return view('admin.api-calls.ledger', [
            'apiCall' => $apiCall,
            'entries' => $entries,
        ]);
    }
}
