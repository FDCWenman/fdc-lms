<?php

namespace App\Actions\Auth;

use App\Models\FailedLoginAttempt;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Authenticate a user with email and password.
     *
     * @throws ValidationException
     */
    public function execute(string $email, string $password): User
    {
        // Check if account is locked
        if (FailedLoginAttempt::isEmailLocked($email)) {
            $lockRecord = FailedLoginAttempt::where('email', $email)
                ->where('locked_until', '>', now())
                ->orderBy('locked_until', 'desc')
                ->first();

            throw ValidationException::withMessages([
                'email' => [
                    'Too many failed login attempts. Your account is locked until ' .
                    $lockRecord->locked_until->format('g:i A') . '. ' .
                    'Please try again later.'
                ],
            ]);
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        // Validate credentials
        if (!$user || !Hash::check($password, $user->password)) {
            // Record failed attempt
            $this->recordFailedAttempt($email);

            // Generic error message (don't reveal if email exists)
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check account status
        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact HR for assistance.'],
            ]);
        }

        if ($user->isPendingVerification() || !$user->isVerified()) {
            throw ValidationException::withMessages([
                'email' => [
                    'Your email has not been verified yet. ' .
                    'Please check your email for the verification link.'
                ],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact HR for assistance.'],
            ]);
        }

        // Clear any failed login attempts for this email
        FailedLoginAttempt::clearAttempts($email);

        // Log successful login
        $this->auditLog->logLoginSuccess($user);

        return $user;
    }

    /**
     * Record a failed login attempt and lock account if necessary.
     */
    protected function recordFailedAttempt(string $email): void
    {
        $ipAddress = Request::ip();

        // Record the attempt
        FailedLoginAttempt::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'attempted_at' => now(),
        ]);

        // Check if we need to lock the account
        $recentAttempts = FailedLoginAttempt::countRecentAttempts($email, 15);

        if ($recentAttempts >= 5) {
            // Lock the account for 30 minutes
            FailedLoginAttempt::lockAccount($email, $ipAddress, 30);

            // Try to log the lockout if user exists
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->auditLog->logAccountLocked($user, 30);
            }
        }
    }
}
