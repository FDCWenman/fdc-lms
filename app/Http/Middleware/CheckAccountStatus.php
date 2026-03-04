<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if account is deactivated
        if ($user->isDeactivated()) {
            auth()->logout();
            
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account has been deactivated. Please contact HR for assistance.',
                ]);
        }

        // Check if account is not verified
        if ($user->isPendingVerification() || !$user->isVerified()) {
            auth()->logout();
            
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your email has not been verified. Please check your email for the verification link.',
                ]);
        }

        // Check if account is not active
        if (!$user->isActive()) {
            auth()->logout();
            
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account is not active. Please contact HR for assistance.',
                ]);
        }

        return $next($request);
    }
}
