<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $checks = [
        'db'    => rescue(fn() => DB::select('SELECT 1') !== null, false),
        'cache' => rescue(fn() => Cache::put('health_probe', 1, 5) !== false, false),
    ];
    $ok = !in_array(false, $checks, true);
    return response()->json(
        ['status' => $ok ? 'ok' : 'down', 'checks' => $checks],
        $ok ? 200 : 503
    );
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
