<?php

use App\Http\Controllers\Auth\VerificationController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RequestNewVerification;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

// Guest routes (login & registration)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');

    // Password reset routes
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    // Verification routes
    Route::get('/auth/verify', [VerificationController::class, 'verify'])->name('auth.verify');
    Route::get('/auth/request-verification', RequestNewVerification::class)->name('auth.request-verification');
});

// Authenticated routes
Route::middleware(['auth', 'user.active'])->group(function () {
    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    // Role Management Routes (Administrator only)
    Route::prefix('admin/roles')->name('admin.roles.')->middleware('role:Administrator')->group(function () {
        Route::get('/', \App\Livewire\Roles\ManageRoles::class)->name('index');
        Route::get('/{roleId}/edit', \App\Livewire\Roles\EditRole::class)->name('edit');
        Route::get('/users/{userId}/assign', \App\Livewire\Roles\AssignRoles::class)->name('assign');
        Route::get('/permissions', \App\Livewire\Roles\ViewPermissions::class)->name('permissions');
        Route::get('/audit-logs', \App\Livewire\Roles\ViewAuditLogs::class)->name('audit-logs');
    });

    // Employee Management Routes
    Route::middleware('can:view-employees')->group(function () {
        Route::get('/employees', \App\Livewire\Employees\ManageEmployees::class)->name('employees.index');
    });

    // Employee dashboard (requires 'employee' role)
    Route::middleware(['role:employee'])->group(function () {
        Route::view('/leaves', 'pages.leaves')->name('leaves');
    });

    // Approver portal (requires hr, team-lead, or project-manager role)
    Route::middleware(['role:hr|team-lead|project-manager'])->group(function () {
        Route::view('/portal', 'pages.portal')->name('portal');
    });

    // Fallback dashboard
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

// Welcome page
Route::view('/', 'welcome')->name('home');

require __DIR__.'/settings.php';
