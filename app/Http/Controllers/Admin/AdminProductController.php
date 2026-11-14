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

    /**
     * 商品列表（JSON API，供 React SPA Admin 后台使用）。
     * 支持 status / type 筛选与 name 关键字搜索。
     */
    public function apiIndex(Request $request)
    {
        $status = $request->query('status');
        $type = $request->query('type');
        $keyword = $request->query('keyword');

        $products = Product::query()
            ->when(
                $status !== null && $status !== '',
                fn ($query) => $query->where('status', (int) $status)
            )
            ->when(
                $type !== null && $type !== '',
                fn ($query) => $query->where('type', (int) $type)
            )
            ->when(
                $keyword !== null && $keyword !== '',
                fn ($query) => $query->where('name', 'like', '%'.$keyword.'%')
            )
            ->orderByDesc('created_at')
            ->paginate(20);

        $list = $products->getCollection()->map(fn (Product $p): array => [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'cover' => $p->cover,
            'price' => $p->price,
            'type' => $p->type,
            'status' => $p->status,
            'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    /**
     * 商品上下架切换（JSON API，供 React SPA Admin 后台使用）。
     */
    public function apiToggleStatus(Request $request, AdminProductService $service, AuditService $auditService, int $id)
    {
        $product = $service->toggleStatus($id);

        $label = $product->status === 1 ? '上架' : '下架';

        $auditService->log(
            (int) $request->user()?->id,
            'admin.product.toggle-status',
            'product',
            $product->id,
            ['status' => $product->status],
        );

        return response()->json([
            'code' => 0,
            'msg' => "商品 {$product->name} 已{$label}",
            'data' => [
                'id' => $product->id,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * 新建商品（JSON API，供 React SPA Admin 后台使用）。
     * 复用与 Web 表单一致的验证规则与 AdminProductService。
     */
    public function apiStore(Request $request, AdminProductService $service, AuditService $auditService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'type' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:0,1',
            'cover' => 'nullable|string|max:255',
        ]);

        $product = $service->create($data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.product.create',
            'product',
            $product->id,
            ['name' => $product->name, 'type' => $product->type, 'price' => $product->price, 'status' => $product->status],
        );

        return response()->json([
            'code' => 0,
            'msg' => "商品 {$product->name} 已创建",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'cover' => $product->cover,
                'price' => $product->price,
                'type' => $product->type,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * 更新商品（JSON API，供 React SPA Admin 后台使用）。
     * 复用与 Web 表单一致的验证规则与 AdminProductService。
     */
    public function apiUpdate(Request $request, AdminProductService $service, AuditService $auditService, int $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'type' => 'required|integer|in:1,2',
            'cover' => 'nullable|string|max:255',
        ]);

        $product = $service->update($id, $data);

        $auditService->log(
            (int) $request->user()->id,
            'admin.product.update',
            'product',
            $product->id,
            ['name' => $product->name, 'type' => $product->type, 'price' => $product->price],
        );

        return response()->json([
            'code' => 0,
            'msg' => "商品 {$product->name} 已更新",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'cover' => $product->cover,
                'price' => $product->price,
                'type' => $product->type,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * 强制下架（JSON API，供 React SPA Admin 后台使用）。
     * 管理员特权操作，不校验商品归属，直接将状态置为 2（强制下架）。
     */
    public function apiForceArchive(Request $request, AuditService $auditService, int $id)
    {
        $product = Product::query()->find($id);

        if (! $product) {
            return response()->json([
                'code' => 1,
                'msg' => '商品不存在',
                'data' => [],
            ], 404);
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

        return response()->json([
            'code' => 0,
            'msg' => "商品 {$product->name} 已强制下架",
            'data' => [
                'id' => $product->id,
                'status' => $product->status,
            ],
        ]);
    }
}
