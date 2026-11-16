<?php

declare(strict_types=1);

use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\DeveloperController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle.user_or_ip:register_per_minute');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.user_or_ip:login_per_minute');
    Route::post('/sms/send', [AuthController::class, 'smsSend'])->middleware('throttle.user_or_ip:sms_per_minute');
    Route::post('/sms/login', [AuthController::class, 'smsLogin'])->middleware('throttle.user_or_ip:sms_per_minute');

    // 第三方 OAuth 登录
    Route::get('/redirect/{provider}', [SocialiteController::class, 'redirect'])->where('provider', 'github|weixin');
    Route::get('/callback/{provider}', [SocialiteController::class, 'callback'])->where('provider', 'github|weixin');
    Route::get('/redirect/alipay', [AlipayController::class, 'redirect']);
    Route::get('/callback/alipay', [AlipayController::class, 'callback']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// 开发者API（自营模式暂时关闭）
// Route::middleware('api')->prefix('api/v1/developers')->group(function (): void {
//     Route::post('/apply', [DeveloperController::class, 'apply'])->middleware('auth:sanctum');
// });

// 开发者API（自营模式暂时关闭）
// Route::middleware(['api', 'auth:sanctum', 'admin'])->prefix('api/v1/admin/developers')->group(function (): void {
//     Route::post('/{id}/audit', [DeveloperController::class, 'audit'])->whereNumber('id');
// });
