<?php

declare(strict_types=1);

use App\Modules\Distributor\Http\Controllers\DistributorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1/distributor')->group(function (): void {
    // 绑定上级达人（幂等，一个用户仅可绑定一次）
    Route::post('/bind', [DistributorController::class, 'bind']);
    // 查询我的上级达人
    Route::get('/relation', [DistributorController::class, 'show']);
});
