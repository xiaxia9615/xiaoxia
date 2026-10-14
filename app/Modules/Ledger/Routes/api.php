<?php

declare(strict_types=1);

use App\Modules\Ledger\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/ledger')->group(function (): void {
    Route::get('/balance', [LedgerController::class, 'balance'])->middleware('auth:sanctum');
    Route::get('/entries', [LedgerController::class, 'entries'])->middleware('auth:sanctum');
});
