<?php

declare(strict_types=1);

use App\Modules\ApiKey\Http\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1/api-keys')->group(function (): void {
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/', [ApiKeyController::class, 'store']);
    Route::delete('/{id}', [ApiKeyController::class, 'destroy'])->whereNumber('id');
});
