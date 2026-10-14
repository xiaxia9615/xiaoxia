<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\ApiKey\Models\ApiCall;
use App\Modules\Product\Models\Product;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'required|string|max:500',
            'price'            => 'required|integer|min:0|max:100000000',
            'endpoint'         => 'required|url|max:255',
            'method'           => 'required|in:GET,POST',
            'api_key'          => 'nullable|string|max:500',
            'request_template' => 'nullable|array',
        ]);

        try {
            $this->contentCheck($data['name'].' '.$data['description']);

            $product = Product::create([
                'user_id'     => $request->user()->id,
                'type'        => 1,
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']).'-'.uniqid(),
                'description' => $data['description'],
                'price'       => $data['price'],
                'status'      => 0,
            ]);

            SkillDetail::create([
                'product_id'       => $product->id,
                'endpoint'         => $data['endpoint'],
                'method'           => $data['method'],
                'api_key'          => $data['api_key'] ?? null,
                'request_template' => $data['request_template'] ?? null,
                'provider_share'   => 80,
                'platform_share'   => 10,
                'influencer_share' => 5,
                'donation_share'   => 5,
            ]);

            return response()->json(['code'=>0,'data'=>['id'=>$product->id,'status'=>'pending']]);
        } catch (RuntimeException $e) {
            return response()->json(['code'=>1,'message'=>$e->getMessage()], 400);
        } catch (\Throwable $e) {
            \Log::error('skill store failed', ['error'=>$e->getMessage()]);
            return response()->json(['code'=>1,'message'=>'系统繁忙，请稍后重试'], 500);
        }
    }

    private function contentCheck(string $text): void
    {
        $blocked = ['赌博','色情','枪支','毒品','诈骗','代考','洗钱','博彩','翻墙','外挂'];
        foreach ($blocked as $word) {
            if (mb_strpos($text, $word) !== false) {
                throw new RuntimeException('内容含违规词，无法上架');
            }
        }
    }

    public function calls(Request $request): JsonResponse
    {
        $calls = ApiCall::where('user_id', $request->user()->id)
            ->with('product:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['code' => 0, 'data' => $calls]);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['code'=>1,'message'=>'无权限'], 403);
        }

        $data = $request->validate([
            'approve' => 'required|boolean',
            'note'    => 'nullable|string|max:255',
        ]);

        $product = Product::where('type', 1)->find($id);
        if (! $product) return response()->json(['code'=>1,'message'=>'not found'], 404);

        $product->update([
            'status'     => $data['approve'] ? 1 : 2,
            'audit_note' => $data['note'] ?? null,
        ]);

        return response()->json(['code'=>0,'data'=>['id'=>$product->id,'status'=>$product->status]]);
    }
}
