<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    public function __construct(
        protected TokenService $tokenService,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Reset a user's password using a reset token.
     *
     * @throws ValidationException
     */
    public function execute(string $token, string $password, string $passwordConfirmation): User
    {
        // Validate password confirmation
        if ($password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password' => ['The password confirmation does not match.'],
            ]);
        }

        // Validate password strength
        $passwordRule = Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();

        try {
            validator(['password' => $password], [
                'password' => ['required', $passwordRule],
            ])->validate();
        } catch (ValidationException $e) {
            throw $e;
        }

        // Validate the token
        $validation = $this->tokenService->validatePasswordResetToken($token);

        if (!$validation['valid']) {
            throw ValidationException::withMessages([
                'token' => [$validation['error']],
            ]);
        }

        $resetToken = $validation['token'];
        $user = $resetToken->user;

        // Update password
        $user->update([
            'password' => Hash::make($password),
        ]);

        // Mark token as used
        $this->tokenService->markPasswordResetTokenAsUsed($resetToken);

        // Invalidate all other user sessions
        // This will force logout on all devices except the current one
        $user->tokens()->delete();

        // Log the password reset
        $this->auditLog->logPasswordReset($user);

        return $user;
    }
}
