<?php

use App\Http\Controllers\Admin\AdminAiProviderController;
use App\Http\Controllers\Admin\AdminApiKeyController;
use App\Http\Controllers\Admin\AdminDeveloperController;
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\AdminLedgerController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminSkillController;
use App\Http\Controllers\Admin\AdminSkillPendingController;
use App\Http\Controllers\Admin\AdminSystemController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWalletController;
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
    Route::get('/api-keys', [AdminApiKeyController::class, 'index'])->name('api-keys.index');
    Route::delete('/api-keys/{id}', [AdminApiKeyController::class, 'revoke'])
        ->whereNumber('id')->name('api-keys.revoke');
    Route::get('/system/health', [AdminSystemController::class, 'health'])->name('system.health');
    Route::get('/system/backups', [AdminSystemController::class, 'backups'])->name('system.backups');
    Route::get('/system/logs', [AdminSystemController::class, 'logs'])->name('system.logs');

    // 开发者审核（自营模式暂时关闭，考取资质后恢复）
    // Route::get('/developers', [AdminDeveloperController::class, 'index'])->name('developers.index');
    // Route::post('/developers/{id}/audit', [AdminDeveloperController::class, 'audit'])->whereNumber('id')->name('developers.audit');

    // 技能审核
    Route::get('/skills/pending', [AdminSkillPendingController::class, 'index'])->name('skills.pending');
    Route::post('/skills/pending/{id}/audit', [AdminSkillPendingController::class, 'audit'])->whereNumber('id')->name('skills.pending.audit');

    // 钱包管理
    Route::get('/wallets', [AdminWalletController::class, 'index'])->name('wallets.index');
    Route::get('/wallets/recharges', [AdminWalletController::class, 'recharges'])->name('wallets.recharges');

    // 订单管理
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{id}/refund', [AdminOrderController::class, 'refund'])->whereNumber('id')->name('orders.refund');

    // 商品管理
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/products/{id}/force-archive', [AdminProductController::class, 'forceArchive'])->whereNumber('id')->name('products.forceArchive');

});

// 财务中心（独立路由组：仅超级管理员与财务人员可访问，不经过 admin 中间件）
Route::middleware(['auth', 'finance'])->prefix('admin/finance')->name('admin.finance.')->group(function (): void {
    Route::get('/', [AdminFinanceController::class, 'index'])->name('index');
    Route::get('/recharges', [AdminFinanceController::class, 'recharges'])->name('recharges');
    Route::get('/withdrawals', [AdminFinanceController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals/{id}/approve', [AdminFinanceController::class, 'approveWithdrawal'])
        ->whereNumber('id')->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/mark-paid', [AdminFinanceController::class, 'markPaid'])
        ->whereNumber('id')->name('withdrawals.mark-paid');
    Route::get('/recharges/export', [AdminFinanceController::class, 'exportRecharges'])->name('recharges.export');
    Route::get('/withdrawals/export', [AdminFinanceController::class, 'exportWithdrawals'])->name('withdrawals.export');
});
