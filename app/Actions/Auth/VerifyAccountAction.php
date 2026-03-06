<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Support\Facades\DB;

/**
 * Verify user account via token
 *
 * Validates verification token and activates user account by setting
 * status to active and recording verification timestamp.
 *
 * Requirements: FR-019, FR-020, FR-021, FR-022, FR-023
 */
class VerifyAccountAction
{
    /**
     * Execute the verification action.
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public function execute(string $token): array
    {
        // Find token
        $verificationToken = VerificationToken::where('token', $token)->first();

        if (! $verificationToken) {
            return [
                'success' => false,
                'message' => 'Invalid verification link. Please request a new verification link.',
            ];
        }

        // Check if already verified
        if ($verificationToken->isVerified()) {
            $user = $verificationToken->user;

            return [
                'success' => true,
                'message' => 'Your account is already verified. You can log in now.',
                'user' => $user,
            ];
        }

        // Check if expired
        if ($verificationToken->isExpired()) {
            return [
                'success' => false,
                'message' => 'This verification link has expired. Please request a new verification link.',
            ];
        }

        // Verify account
        DB::beginTransaction();

        try {
            $user = $verificationToken->user;

            // Update user status to active
            $user->update([
                'status' => 1, // active
                'verified_at' => now(),
            ]);

            // Mark token as verified
            $verificationToken->markAsVerified();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Your account has been successfully verified! You can now log in.',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Account verification failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during verification. Please try again or contact support.',
            ];
        }
    }

    /**
     * Request a new verification link for a user.
     *
     * @return array{success: bool, message: string}
     */
    public function requestNewVerification(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return [
                'success' => false,
                'message' => 'No account found with this email address.',
            ];
        }

        // Check if already verified
        if ($user->verified_at !== null) {
            return [
                'success' => false,
                'message' => 'Your account is already verified. You can log in now.',
            ];
        }

        // Check if account is deactivated
        if ($user->status === 0) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact HR for assistance.',
            ];
        }

        DB::beginTransaction();

        try {
            // Invalidate old tokens
            VerificationToken::where('user_id', $user->id)
                ->whereNull('verified_at')
                ->delete();

            // Generate new token
            $tokenString = \Str::random(64);
            $verificationToken = VerificationToken::create([
                'user_id' => $user->id,
                'token' => $tokenString,
                'expires_at' => now()->addHours(24),
            ]);

            // Send new verification DM (only in production/staging)
            if (config('app.env') !== 'local') {
                $verificationUrl = route('auth.verify', ['token' => $tokenString]);
                $slackService = app(\App\Services\SlackService::class);
                $slackService->sendVerificationDM($user->slack_id, $verificationUrl);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'A new verification link has been sent to your Slack account.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('New verification request failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send verification link. Please try again later.',
            ];
        }
    }
}
