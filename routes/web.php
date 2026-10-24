<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('users.show');
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])->whereNumber('id')->name('users.updateRole');
    Route::post('/users/{id}/toggle', [AdminUserController::class, 'toggle'])->whereNumber('id')->name('users.toggle');
    // 后续资源路由陆续添加
});
