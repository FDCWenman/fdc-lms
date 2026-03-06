<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Route;

// Guest routes (login)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
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

    // Employee dashboard (requires 'employee' role)
    Route::middleware(['role:employee'])->group(function () {
        Route::view('/leaves', 'pages.leaves')->name('leaves');
    });

    // Approver portal (requires hr, team-lead, or project-manager role)
    Route::middleware(['role:hr|team-lead|project-manager'])->group(function () {
        Route::view('/portal', 'pages.portal')->name('portal');
    });

    // HR Admin routes (requires 'hr' role)
    Route::middleware(['role:hr'])->group(function () {
        Route::get('/register', Register::class)->name('register');
    });

    // Fallback dashboard
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

// Welcome page
Route::view('/', 'welcome')->name('home');

require __DIR__.'/settings.php';
