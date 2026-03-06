<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Login - FDCLeave')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        // If already authenticated, redirect to appropriate dashboard
        if (Auth::check()) {
            $this->redirectToDashboard();
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rate limiting
        $this->ensureIsNotRateLimited();

        // Attempt authentication
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        // Clear rate limiter
        RateLimiter::clear($this->throttleKey());

        // Regenerate session
        request()->session()->regenerate();

        // Redirect to appropriate dashboard based on role
        $this->redirectToDashboard();
    }

    protected function redirectToDashboard(): void
    {
        $user = Auth::user();

        if ($user->hasRole('employee')) {
            $this->redirect('/leaves', navigate: true);
        } elseif ($user->isApprover()) {
            $this->redirect('/portal', navigate: true);
        } else {
            $this->redirect('/dashboard', navigate: true);
        }
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return strtolower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
