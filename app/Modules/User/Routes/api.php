<?php

declare(strict_types=1);

use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\DeveloperController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle.user_or_ip:register_per_minute');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.user_or_ip:login_per_minute');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// 开发者入驻：普通用户申请
Route::middleware('api')->prefix('api/v1/developers')->group(function (): void {
    Route::post('/apply', [DeveloperController::class, 'apply'])->middleware('auth:sanctum');
});

// 开发者入驻：管理员审核（独立分组，仅管理员可访问）
Route::middleware(['api', 'auth:sanctum', 'admin'])->prefix('api/v1/admin/developers')->group(function (): void {
    Route::post('/{id}/audit', [DeveloperController::class, 'audit'])->whereNumber('id');
});
