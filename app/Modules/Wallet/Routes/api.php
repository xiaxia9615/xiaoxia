<?php

declare(strict_types=1);

use App\Modules\Wallet\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1/wallet')->group(function (): void {
    // 查询当前登录用户钱包余额
    Route::get('/', [WalletController::class, 'show']);
    // 冻结资金（余额不足返回 402）
    Route::post('/freeze', [WalletController::class, 'freeze']);
    // 模拟充值
    Route::post('/deposit', [WalletController::class, 'deposit']);
});
