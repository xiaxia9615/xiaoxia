<?php

use App\Http\Controllers\Api\PaymentNotifyController;
use App\Modules\Order\Http\Controllers\OrderController;
use App\Modules\Product\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $checks = [
        'db' => rescue(fn () => DB::select('SELECT 1') !== null, false),
        'cache' => rescue(fn () => Cache::put('health_probe', 1, 5) !== false, false),
    ];
    $ok = ! in_array(false, $checks, true);

    return response()->json(
        ['status' => $ok ? 'ok' : 'down', 'checks' => $checks],
        $ok ? 200 : 503
    );
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

if (config('app.env') !== 'production') {
    Route::prefix('v1/payment/notify')->group(function (): void {
        Route::post('/wechat', [PaymentNotifyController::class, 'wechat']);
        Route::post('/alipay', [PaymentNotifyController::class, 'alipay']);
    });
}

if (config('app.env') === 'testing') {
    Route::post('v1/payment/notify/mock', [PaymentNotifyController::class, 'mock']);
}

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('v1/orders', [OrderController::class, 'index']);
    Route::get('v1/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::get('v1/orders/{id}/status', [OrderController::class, 'status'])->whereNumber('id');
    Route::get('v1/products/categories', [ProductController::class, 'categories']);
});

if (config('app.env') !== 'production') {
    // 开发阶段兜底，防止前端未开发接口报 404
    Route::fallback(function (Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'code' => 0,
                'msg' => 'mock data',
                'data' => [],
            ]);
        }

        abort(404);
    });
}
