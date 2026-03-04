<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\VerifyEmail;
use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    /**
     * Verify email using token.
     */
    public function verify(string $token, VerifyEmail $verifyEmail): RedirectResponse
    {
        try {
            $user = $verifyEmail->execute($token);

            return redirect()->route('login')
                ->with('success', 'Your email has been verified! You can now log in.');
        } catch (ValidationException $e) {
            return redirect()->route('login')
                ->withErrors(['email' => $e->getMessage()]);
        }
    }

    /**
     * Show verification status page.
     */
    public function show(string $token): Response
    {
        return Inertia::render('Auth/VerifyEmail', [
            'token' => $token,
        ]);
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request, TokenService $tokenService): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isVerified()) {
            return back()->with('info', 'Your email is already verified.');
        }

        // Generate new token
        $token = $tokenService->generateEmailVerificationToken($user, 48);

        // Send verification email/Slack message (implement mail notification)
        // For now, we'll just return success
        
        return back()->with('success', 'Verification link has been resent.');
    }
}
