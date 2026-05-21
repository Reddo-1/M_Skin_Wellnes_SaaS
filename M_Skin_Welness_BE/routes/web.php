<?php

use App\Http\Controllers\Admin\{AccountController, AuditLogController, CenterController, DashboardController, LoginController, PlanController};
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'role:superadmin'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('centers', [CenterController::class, 'index'])->name('centers.index');
        Route::get('centers/{center}', [CenterController::class, 'show'])->name('centers.show');
        Route::post('centers/{center}/impersonate', [CenterController::class, 'impersonate'])->name('centers.impersonate');

        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('account', [AccountController::class, 'update'])->name('account.update');
    });
});
