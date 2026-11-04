<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminProductController extends Controller
{
    /**
     * 商品列表：分页展示全部商品，预加载卖家，支持按状态筛选
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $products = Product::query()
            ->with('provider')
            ->when(
                $status !== null && $status !== '',
                fn ($query) => $query->where('status', (int) $status)
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', ['products' => $products]);
    }

    /**
     * 强制下架：管理员特权操作，不校验商品归属，直接将状态置为 2（下架）
     */
    public function forceArchive(Request $request, int $id)
    {
        $product = Product::query()->find($id);

        if (! $product) {
            throw new RuntimeException('商品不存在');
        }

        $product->status = 2;
        $product->save();

        Log::info('admin.product_force_archived', [
            'operator' => $request->user()?->id,
            'product_id' => $product->id,
            'seller_id' => $product->user_id,
            'ip' => $request->ip(),
        ]);

        return back()->with('status', "商品 {$product->name} 已强制下架");
    }
}
