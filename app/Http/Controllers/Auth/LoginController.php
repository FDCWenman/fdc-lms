<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request, AuthenticateUser $authenticateUser): RedirectResponse
    {
        $user = $authenticateUser->execute(
            $request->input('email'),
            $request->input('password')
        );

        // Create session token
        auth()->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // Redirect based on role
        if ($user->hasAnyRole(['HR Approver', 'Lead Approver', 'PM Approver'])) {
            return redirect()->intended('/portal');
        }

        return redirect()->intended('/leaves');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request, LogoutUser $logoutUser): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $logoutUser->execute($user);
        }

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
