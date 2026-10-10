<?php

declare(strict_types=1);

use App\Modules\Product\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show'])->whereNumber('id');
});
