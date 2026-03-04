<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\SlackService;
use App\Services\TokenService;
use Illuminate\Support\Facades\Request;

class RequestPasswordReset
{
    public function __construct(
        protected TokenService $tokenService,
        protected SlackService $slackService
    ) {}

    /**
     * Request a password reset for a user.
     * Always returns success to avoid email enumeration.
     */
    public function execute(string $email): array
    {
        // Find user by email
        $user = User::where('email', $email)->first();

        // If user doesn't exist, return success anyway (prevent email enumeration)
        if (!$user) {
            return [
                'success' => true,
                'message' => 'If an account exists with this email, you will receive a password reset link via Slack.',
            ];
        }

        // Check if user has Slack ID
        if (!$user->slack_id) {
            return [
                'success' => true,
                'message' => 'If an account exists with this email, you will receive a password reset link via Slack.',
            ];
        }

        // Generate reset token (1 hour expiry)
        $resetToken = $this->tokenService->generatePasswordResetToken(
            $user,
            Request::ip(),
            60 // 1 hour
        );

        // Generate reset URL
        $resetUrl = url('/reset-password/' . $resetToken->token);

        // Send Slack DM
        $message = "Hello {$user->name},\n\n" .
            "You requested a password reset for your FDCLeave account.\n\n" .
            "Click the link below to reset your password:\n" .
            "{$resetUrl}\n\n" .
            "This link will expire in 1 hour.\n\n" .
            "If you didn't request this, please ignore this message.";

        $result = $this->slackService->sendDirectMessage($user->slack_id, $message);

        if (!$result['success']) {
            // Log the error but still return success to user
            \Log::error('Failed to send password reset Slack DM', [
                'user_id' => $user->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }

        return [
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset link via Slack.',
        ];
    }
}
