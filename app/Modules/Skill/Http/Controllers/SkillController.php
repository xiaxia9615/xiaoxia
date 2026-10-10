<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Http\JsonResponse;

final class SkillController
{
    public function index(): JsonResponse
    {
        $list = Product::where('type', 1)->where('status', 1)
            ->with('provider:id,name')->orderByDesc('id')->paginate(20);
        return response()->json(['code'=>0,'data'=>$list]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::where('type', 1)->with('provider:id,name')->find($id);
        if (! $product) return response()->json(['code'=>1,'message'=>'not found'], 404);
        $detail = SkillDetail::where('product_id', $id)->first();
        return response()->json(['code'=>0,'data'=>['product'=>$product,'detail'=>$detail]]);
    }
}
