<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\RedirectByRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'requestReset'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.update');

    // Email Verification (public link)
    Route::get('/verify-email/{token}', [VerificationController::class, 'verify'])
        ->name('verification.verify');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Home redirect based on role
    Route::get('/', RedirectByRole::class)->name('home');

    // Email Verification Resend
    Route::post('/verification/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend');

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/approvers', [ProfileController::class, 'updateApprovers'])
            ->name('profile.approvers');
        Route::post('/password', [ProfileController::class, 'changePassword'])
            ->name('profile.password');
        Route::post('/slack-refresh', [ProfileController::class, 'refreshSlackName'])
            ->name('profile.slack-refresh');
    });

    // Placeholder routes for leave management (to be implemented)
    Route::get('/leaves', function () {
        return inertia('Leaves/Dashboard');
    })->name('leaves.dashboard');

    Route::get('/portal', function () {
        return inertia('Portal/Calendar');
    })->name('portal.calendar');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (HR Only)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->group(function () {
    // Account Management
    Route::resource('accounts', AccountController::class);
    Route::post('/accounts/{account}/activate', [AccountController::class, 'activate'])
        ->name('accounts.activate');
    Route::post('/accounts/{account}/deactivate', [AccountController::class, 'deactivate'])
        ->name('accounts.deactivate');
});

require __DIR__.'/settings.php';
