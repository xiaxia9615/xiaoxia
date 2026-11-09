<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Services\AdminProductService;
use App\Modules\System\Services\AuditService;
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
    public function forceArchive(Request $request, AuditService $auditService, int $id)
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

        $auditService->log(
            (int) $request->user()->id,
            'admin.product_force_archived',
            'product',
            $product->id,
            ['seller_id' => $product->user_id, 'name' => $product->name],
        );

        return back()->with('status', "商品 {$product->name} 已强制下架");
    }

    /**
     * 新建商品表单
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * 保存新商品
     */
    public function store(Request $request, AdminProductService $service, AuditService $auditService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'type' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:0,1',
        ]);

        $product = $service->create($data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.product.create',
            'product',
            $product->id,
            ['name' => $product->name, 'type' => $product->type, 'price' => $product->price, 'status' => $product->status],
        );

        return redirect()->route('admin.products.index')->with('status', "商品 {$product->name} 已创建");
    }

    /**
     * 编辑商品表单
     */
    public function edit(int $id)
    {
        $product = Product::findOrFail($id);

        return view('admin.products.edit', ['product' => $product]);
    }

    /**
     * 更新商品
     */
    public function update(Request $request, AdminProductService $service, AuditService $auditService, int $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'type' => 'required|integer|in:1,2',
        ]);

        $product = $service->update($id, $data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.product.update',
            'product',
            $product->id,
            ['name' => $product->name, 'type' => $product->type, 'price' => $product->price],
        );

        return redirect()->route('admin.products.index')->with('status', "商品 {$product->name} 已更新");
    }

    /**
     * 切换商品上下架状态
     */
    public function toggleStatus(Request $request, AdminProductService $service, int $id)
    {
        $product = $service->toggleStatus($id);

        $label = $product->status === 1 ? '上架' : '下架';

        return back()->with('status', "商品 {$product->name} 已{$label}");
    }
}
