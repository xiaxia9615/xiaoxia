<?php

use App\Http\Controllers\Api\PaymentNotifyController;
use App\Modules\Address\Http\Controllers\AddressController;
use App\Modules\Aftersale\Http\Controllers\AftersaleController;
use App\Modules\Article\Http\Controllers\ArticleController;
use App\Modules\Browse\Http\Controllers\BrowseController;
use App\Modules\Cart\Http\Controllers\CartController;
use App\Modules\Comment\Http\Controllers\CommentController;
use App\Modules\Favor\Http\Controllers\FavorController;
use App\Modules\Goods\Http\Controllers\GoodsController;
use App\Modules\Message\Http\Controllers\MessageController;
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
})->middleware('auth:***');

if (config('app.env') !== 'production') {
    Route::prefix('v1/payment/notify')->group(function (): void {
        Route::post('/wechat', [PaymentNotifyController::class, 'wechat']);
        Route::post('/alipay', [PaymentNotifyController::class, 'alipay']);
    });
}

if (config('app.env') === 'testing') {
    Route::post('v1/payment/notify/mock', [PaymentNotifyController::class, 'mock']);
}

// ===== 公开路由（无需登录） =====

Route::prefix('v1')->group(function (): void {
    // Article（文章）
    Route::get('article/index', [ArticleController::class, 'index']);
    Route::get('article/category', [ArticleController::class, 'category']);
    Route::get('article/detail', [ArticleController::class, 'detail']);

    // Goods（商品）
    Route::get('goods/index', [GoodsController::class, 'index']);
    Route::get('goods/category', [GoodsController::class, 'category']);
    Route::get('goods/detail', [GoodsController::class, 'detail']);

    // Comment（评论）
    Route::get('comments/index', [CommentController::class, 'index']);

    // Security
    Route::get('security/publickey', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });

    // Verify
    Route::post('verify/send', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
});

// ===== 需登录路由 =====

Route::middleware('auth:***')->prefix('v1')->group(function (): void {
    // Cart（购物车）
    Route::get('cart/index', [CartController::class, 'index']);
    Route::post('cart/save', [CartController::class, 'save']);
    Route::post('cart/delete', [CartController::class, 'delete']);

    // Address（收货地址）
    Route::get('address/index', [AddressController::class, 'index']);
    Route::post('address/save', [AddressController::class, 'save']);
    Route::post('address/delete', [AddressController::class, 'delete']);
    Route::post('address/default', [AddressController::class, 'setDefault']);

    // Favor（收藏）
    Route::get('favor/index', [FavorController::class, 'index']);
    Route::get('favor/status', [FavorController::class, 'status']);
    Route::post('favor/save', [FavorController::class, 'save']);
    Route::post('favor/delete', [FavorController::class, 'delete']);

    // Message（消息）
    Route::get('message/index', [MessageController::class, 'index']);
    Route::get('message/unread', [MessageController::class, 'unread']);
    Route::post('message/read', [MessageController::class, 'read']);

    // Browse（浏览历史）
    Route::get('browse/index', [BrowseController::class, 'index']);

    // Aftersale（售后）
    Route::get('aftersale/index', [AftersaleController::class, 'index']);
    Route::post('aftersale/save', [AftersaleController::class, 'save']);
    Route::post('aftersale/cancel', [AftersaleController::class, 'cancel']);

    // Order（订单：额外接口，补充现有的 v1/orders）
    Route::get('order/index', [OrderController::class, 'orderIndex']);
    Route::get('order/detail', [OrderController::class, 'orderDetail']);
    Route::post('order/cancel', [OrderController::class, 'orderCancel']);
    Route::post('order/delete', [OrderController::class, 'orderDelete']);

    // Buy / Pay
    Route::post('buy/index', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::post('buy/add', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::post('pay/submit', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::get('pay/query', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });

    // User（用户：额外接口）
    Route::get('user/profile', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::post('user/update', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::post('user/password', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });
    Route::get('user/integral', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => [], 'total' => 0]]);
    });
    Route::post('user/reset-password', function () {
        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    });

    // 原有路由（保留）
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::get('orders/{id}/status', [OrderController::class, 'status'])->whereNumber('id');
    Route::get('products/categories', [ProductController::class, 'categories']);
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
