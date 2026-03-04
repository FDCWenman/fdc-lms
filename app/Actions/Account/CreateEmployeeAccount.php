<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SlackService;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateEmployeeAccount
{
    public function __construct(
        protected TokenService $tokenService,
        protected SlackService $slackService,
        protected AuditLogService $auditLog
    ) {}

    /**
     * Create a new employee account.
     *
     * @throws ValidationException
     */
    public function execute(
        string $name,
        string $email,
        string $slackId,
        int $primaryRoleId,
        ?int $secondaryRoleId = null,
        ?string $hiredDate = null,
        User $createdBy
    ): User {
        // Validate Slack ID
        $slackValidation = $this->slackService->validateSlackId($slackId);
        
        if (!$slackValidation['valid']) {
            throw ValidationException::withMessages([
                'slack_id' => ['Invalid Slack ID: ' . ($slackValidation['error'] ?? 'Unknown error')],
            ]);
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['An account with this email already exists.'],
            ]);
        }

        // Check if Slack ID already exists
        if (User::where('slack_id', $slackId)->exists()) {
            throw ValidationException::withMessages([
                'slack_id' => ['An account with this Slack ID already exists.'],
            ]);
        }

        // Create user with temporary password (they'll reset it via email)
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'slack_id' => $slackId,
            'password' => Hash::make(\Str::random(32)), // Random temp password
            'status' => User::STATUS_FOR_VERIFICATION,
            'primary_role_id' => $primaryRoleId,
            'secondary_role_id' => $secondaryRoleId,
            'hired_date' => $hiredDate ? \Carbon\Carbon::parse($hiredDate) : null,
        ]);

        // Assign roles using Spatie
        $user->assignRole($primaryRoleId);
        if ($secondaryRoleId) {
            $user->assignRole($secondaryRoleId);
        }

        // Generate verification token
        $verificationToken = $this->tokenService->generateEmailVerificationToken($user, 48);

        // Generate verification URL
        $verificationUrl = url('/verify-email/' . $verificationToken->token);

        // Send verification email (you'll need to implement this)
        // For now, we'll send via Slack
        $message = "Welcome to FDCLeave, {$name}!\n\n" .
            "Your account has been created. Please verify your email by clicking the link below:\n" .
            "{$verificationUrl}\n\n" .
            "This link will expire in 48 hours.\n\n" .
            "After verification, you can log in and set up your password.";

        $this->slackService->sendDirectMessage($slackId, $message);

        // Add user to Slack leave channel
        $channelResult = $this->slackService->addToChannel($slackId);
        
        if (!$channelResult['success']) {
            \Log::warning('Failed to add user to Slack channel', [
                'user_id' => $user->id,
                'error' => $channelResult['error'] ?? 'Unknown error',
            ]);
        }

        // Log account creation
        $this->auditLog->logAccountCreated($user, $createdBy, 'New employee account created');

        return $user;
    }
}
