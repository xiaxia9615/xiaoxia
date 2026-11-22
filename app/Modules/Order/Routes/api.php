<?php

declare(strict_types=1);

use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'uid.auth'])->prefix('api/v1/orders')->group(function (): void {
    // 创建订单（不扣款，状态为待支付）
    Route::post('/', [OrderController::class, 'store']);
    // 支付订单（模拟支付，幂等）
    Route::post('/{id}/pay', [OrderController::class, 'pay'])->whereNumber('id');
    // 取消订单（仅待支付订单可取消）
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');
});

// 前端购物车/单品下单入口
Route::middleware(['api', 'uid.auth'])->prefix('api/v1/buy')->group(function (): void {
    Route::post('/add', [OrderController::class, 'buyAdd']);
    Route::post('/index', [OrderController::class, 'buyIndex']);
});

// 前端支付入口
Route::middleware(['api', 'uid.auth'])->prefix('api/v1/pay')->group(function (): void {
    Route::post('/submit', [OrderController::class, 'paySubmit']);
    Route::get('/query', [OrderController::class, 'payQuery']);
});
