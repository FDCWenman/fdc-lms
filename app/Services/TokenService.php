<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate a secure random token.
     */
    protected function generateSecureToken(): string
    {
        return Str::random(64);
    }

    /**
     * Generate a password reset token for a user.
     *
     * @param User $user
     * @param string|null $ipAddress
     * @param int $expiryMinutes Token expiry in minutes (default: 60)
     * @return PasswordResetToken
     */
    public function generatePasswordResetToken(
        User $user,
        ?string $ipAddress = null,
        int $expiryMinutes = 60
    ): PasswordResetToken {
        // Invalidate any existing unused tokens
        PasswordResetToken::where('user_id', $user->id)
            ->where('used', false)
            ->update(['used' => true]);

        // Create new token
        return PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => $this->generateSecureToken(),
            'ip_address' => $ipAddress,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'used' => false,
        ]);
    }

    /**
     * Generate an email verification token for a user.
     *
     * @param User $user
     * @param int $expiryHours Token expiry in hours (default: 48)
     * @return EmailVerificationToken
     */
    public function generateEmailVerificationToken(
        User $user,
        int $expiryHours = 48
    ): EmailVerificationToken {
        // Delete any existing tokens for this user
        EmailVerificationToken::where('user_id', $user->id)->delete();

        // Create new token
        return EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => $this->generateSecureToken(),
            'expires_at' => now()->addHours($expiryHours),
        ]);
    }

    /**
     * Validate a password reset token.
     *
     * @param string $token
     * @return array{valid: bool, token?: PasswordResetToken, error?: string}
     */
    public function validatePasswordResetToken(string $token): array
    {
        $resetToken = PasswordResetToken::where('token', $token)->first();

        if (!$resetToken) {
            return ['valid' => false, 'error' => 'Invalid token'];
        }

        if ($resetToken->used) {
            return ['valid' => false, 'error' => 'Token has already been used'];
        }

        if ($resetToken->isExpired()) {
            return ['valid' => false, 'error' => 'Token has expired'];
        }

        return ['valid' => true, 'token' => $resetToken];
    }

    /**
     * Validate an email verification token.
     *
     * @param string $token
     * @return array{valid: bool, token?: EmailVerificationToken, error?: string}
     */
    public function validateEmailVerificationToken(string $token): array
    {
        $verificationToken = EmailVerificationToken::where('token', $token)->first();

        if (!$verificationToken) {
            return ['valid' => false, 'error' => 'Invalid token'];
        }

        if ($verificationToken->isExpired()) {
            return ['valid' => false, 'error' => 'Token has expired'];
        }

        return ['valid' => true, 'token' => $verificationToken];
    }

    /**
     * Mark a password reset token as used.
     *
     * @param PasswordResetToken $token
     */
    public function markPasswordResetTokenAsUsed(PasswordResetToken $token): void
    {
        $token->markAsUsed();
    }

    /**
     * Delete an email verification token.
     *
     * @param EmailVerificationToken $token
     */
    public function deleteEmailVerificationToken(EmailVerificationToken $token): void
    {
        $token->delete();
    }

    /**
     * Clean up expired tokens (run periodically via scheduled command).
     */
    public function cleanupExpiredTokens(): array
    {
        $deletedPasswordResets = PasswordResetToken::where('expires_at', '<', now())
            ->where('used', true)
            ->delete();

        $deletedEmailVerifications = EmailVerificationToken::where('expires_at', '<', now())
            ->delete();

        return [
            'password_resets_deleted' => $deletedPasswordResets,
            'email_verifications_deleted' => $deletedEmailVerifications,
        ];
    }
}
