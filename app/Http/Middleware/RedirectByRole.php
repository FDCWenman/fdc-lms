<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    /**
     * Handle an incoming request.
     * 
     * Redirects users to the appropriate page based on their role:
     * - Employees → /leaves (leave dashboard)
     * - Approvers (HR/Lead/PM) → /portal (calendar portal)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user is an approver (HR, Lead, or PM)
        if ($user->hasAnyRole(['HR Approver', 'Lead Approver', 'PM Approver'])) {
            return redirect('/portal');
        }

        // Default to employee dashboard
        return redirect('/leaves');
    }
}
