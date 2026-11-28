<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 商品封面图上传。
 *
 * - 存 storage/app/public/products/
 * - 返回相对 URL /storage/products/xxx.jpg（同域，前端 <img> 直接用）
 * - 三重验证：扩展名白名单 + MIME 白名单 + 5MB 上限
 */
final class AdminProductCoverController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif',
                'max:5120',
            ],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = Str::random(24).'.'.$ext;

        $path = $file->storeAs('products', $name, 'public');
        if ($path === false) {
            return response()->json(['code' => 1, 'message' => '上传失败'], 500);
        }

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
            ],
        ]);
    }
}
