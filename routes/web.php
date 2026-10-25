<?php

use App\Http\Controllers\Admin\AdminAiProviderController;
use App\Http\Controllers\Admin\AdminLedgerController;
use App\Http\Controllers\Admin\AdminSkillController;
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

    Route::get('/ledger', [AdminLedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/{accountId}/entries', [AdminLedgerController::class, 'entries'])
        ->whereNumber('accountId')->name('ledger.entries');

    Route::get('/ai-providers', [AdminAiProviderController::class, 'index'])->name('ai-providers.index');
    Route::get('/ai-providers/{id}/models', [AdminAiProviderController::class, 'models'])
        ->whereNumber('id')->name('ai-providers.models');
    Route::get('/skills', [AdminSkillController::class, 'index'])->name('skills.index');
    Route::get('/skills/calls', [AdminSkillController::class, 'calls'])->name('skills.calls');
    Route::get('/skills/{id}', [AdminSkillController::class, 'show'])
        ->whereNumber('id')->name('skills.show');
});
