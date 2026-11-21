<?php

use Illuminate\Support\Facades\Route;

// 统一的前端 SPA 入口，所有非 API 请求统统交给 React
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '^(?!api).*$');
