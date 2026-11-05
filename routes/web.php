<?php

use Illuminate\Support\Facades\Route;

// 全站前端重构中
Route::get('/', function () {
    return '<h1 style="text-align:center; margin-top: 20%; font-family: sans-serif; color: #333;">嗣夏平台前端重构中，敬请期待。</h1>';
});

// 暂时保留后台路由入口（但页面已被清空），如果需要连后台路由一起清空，请把下面这行也注释掉
// require base_path('routes/admin.php'); 
