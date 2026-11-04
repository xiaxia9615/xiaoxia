<?php

declare(strict_types=1);

use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1/orders')->group(function (): void {
    // 创建订单（不扣款，状态为待支付）
    Route::post('/', [OrderController::class, 'store']);
    // 支付订单（模拟支付，幂等）
    Route::post('/{id}/pay', [OrderController::class, 'pay'])->whereNumber('id');
    // 取消订单（仅待支付订单可取消）
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');
});
