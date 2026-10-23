<?php

declare(strict_types=1);

use App\Modules\User\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.user_or_ip:login_per_minute');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
