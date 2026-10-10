<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;

final class ProductController
{
    public function index(): JsonResponse
    {
        $list = Product::where('type', 2)->where('status', 1)->with('provider:id,name')->orderByDesc('id')->paginate(20);
        return response()->json(['code'=>0,'data'=>$list]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::where('type', 2)->with('provider:id,name')->find($id);
        if (! $product) return response()->json(['code'=>1,'message'=>'not found'], 404);
        return response()->json(['code'=>0,'data'=>$product]);
    }
}
