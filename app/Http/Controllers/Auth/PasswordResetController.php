<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RequestPasswordReset;
use App\Actions\Auth\ResetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotPasswordForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Handle forgot password request.
     */
    public function requestReset(
        ForgotPasswordRequest $request,
        RequestPasswordReset $requestPasswordReset
    ): RedirectResponse {
        $result = $requestPasswordReset->execute($request->input('email'));

        return back()->with('success', $result['message']);
    }

    /**
     * Show the reset password form.
     */
    public function showResetPasswordForm(string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
        ]);
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPassword $resetPassword
    ): RedirectResponse {
        try {
            $resetPassword->execute(
                $request->input('token'),
                $request->input('password'),
                $request->input('password_confirmation')
            );

            return redirect()->route('login')
                ->with('success', 'Your password has been reset successfully! You can now log in.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('token'));
        }
    }
}
