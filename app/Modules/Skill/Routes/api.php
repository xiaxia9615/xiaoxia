<?php

declare(strict_types=1);

use App\Modules\Skill\Http\Controllers\SkillCallController;
use App\Modules\Skill\Http\Controllers\SkillController;
use App\Modules\Skill\Http\Controllers\SkillGatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/skills')->group(function (): void {
    Route::get('/', [SkillController::class, 'index']);
    Route::get('/calls', [SkillController::class, 'calls'])->middleware('auth:sanctum');
    Route::get('/{id}', [SkillController::class, 'show'])->whereNumber('id');
    Route::post('/', [SkillController::class, 'store'])->middleware('auth:sanctum');
    Route::patch('/{id}/audit', [SkillController::class, 'audit'])->whereNumber('id')->middleware('auth:sanctum');
    Route::post('/{id}/call', SkillCallController::class)->whereNumber('id')->middleware(['auth:sanctum', 'throttle.user_or_ip:gateway_per_minute']);
    Route::post('/gateway', SkillGatewayController::class)->middleware(['auth:sanctum', 'throttle.user_or_ip:gateway_per_minute']);
});
