<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\VerifyAccountAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handle account verification via token
 *
 * Processes verification links sent via Slack DM and activates user accounts.
 *
 * Requirements: FR-019, FR-020, FR-021, FR-022
 */
class VerificationController
{
    /**
     * Verify user account with token
     *
     * @param  Request  $request
     * @param  VerifyAccountAction  $action
     * @return \Illuminate\View\View
     */
    public function verify(Request $request, VerifyAccountAction $action)
    {
        $token = $request->query('token');

        if (! $token) {
            return view('auth.verification-result', [
                'success' => false,
                'message' => 'Invalid verification link. No token provided.',
            ]);
        }

        Log::info('Account verification attempt', ['token' => substr($token, 0, 10).'...']);

        $result = $action->execute($token);

        Log::info('Account verification result', [
            'success' => $result['success'],
            'has_user' => isset($result['user']),
        ]);

        return view('auth.verification-result', [
            'success' => $result['success'],
            'message' => $result['message'],
            'user' => $result['user'] ?? null,
        ]);
    }
}
