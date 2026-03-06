<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     * Redirects authenticated users away from login/registration pages.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Redirect based on role
                if ($user->hasRole('employee')) {
                    return redirect('/leaves');
                }

                if ($user->isApprover()) {
                    return redirect('/portal');
                }

                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}
