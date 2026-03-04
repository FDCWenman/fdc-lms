<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TokenService;
use Illuminate\Validation\ValidationException;

class VerifyEmail
{
    public function __construct(
        protected TokenService $tokenService,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Verify a user's email using the verification token.
     *
     * @throws ValidationException
     */
    public function execute(string $token): User
    {
        // Validate the token
        $validation = $this->tokenService->validateEmailVerificationToken($token);

        if (!$validation['valid']) {
            throw ValidationException::withMessages([
                'token' => [$validation['error']],
            ]);
        }

        $verificationToken = $validation['token'];
        $user = $verificationToken->user;

        // Check if already verified
        if ($user->isVerified()) {
            throw ValidationException::withMessages([
                'token' => ['Email has already been verified.'],
            ]);
        }

        // Mark user as verified and active
        $user->update([
            'verified_at' => now(),
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
        ]);

        // Delete the verification token
        $this->tokenService->deleteEmailVerificationToken($verificationToken);

        // Log the verification
        $this->auditLog->logEmailVerified($user);

        return $user;
    }
}
