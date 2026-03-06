<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\VerificationToken;
use App\Services\SlackService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Register a new user in the system
 *
 * Creates a new user account with "for_verification" status, generates a verification token,
 * sends Slack DM with verification link, and adds user to Slack channel.
 *
 * Requirements: FR-012, FR-013, FR-014, FR-015, FR-016, FR-017, FR-018, FR-038
 */
class RegisterUserAction
{
    /**
     * Create a new RegisterUserAction instance.
     */
    public function __construct(
        protected SlackService $slackService
    ) {
    }

    /**
     * Execute the registration action.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     slack_id: string,
     *     roles: array<string>,
     *     default_approvers: array{hr_id?: int, tl_id?: int, pm_id?: int}
     * }  $data
     * @return User
     *
     * @throws \RuntimeException When Slack API validation fails in production/staging
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When role doesn't exist
     */
    public function execute(array $data): User
    {
        // Validate Slack ID via API (environment-aware: FR-011, FR-035)
        if (config('app.env') !== 'local') {
            $isValid = $this->slackService->validateSlackId($data['slack_id']);
            if (! $isValid) {
                throw new \RuntimeException('Invalid Slack ID or Slack API unavailable');
            }
        }

        // Check for duplicate Slack ID (FR-038)
        if (User::where('slack_id', $data['slack_id'])->exists()) {
            throw new \RuntimeException('Slack ID already exists in system');
        }

        // Create user with "for_verification" status (FR-014)
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']), // FR-002
            'slack_id' => $data['slack_id'],
            'status' => 2, // for_verification
            'verified_at' => null,
            'default_approvers' => $data['default_approvers'], // FR-016
        ]);

        // Assign roles using Spatie (FR-015)
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        // Generate verification token
        $token = $this->generateVerificationToken($user);

        // Send verification DM and add to Slack channel (FR-017, FR-018)
        if (config('app.env') !== 'local') {
            $verificationUrl = route('auth.verify', ['token' => $token->token]);
            $this->slackService->sendVerificationDM($data['slack_id'], $verificationUrl);
            $this->slackService->addToChannel($data['slack_id']);
        }

        return $user;
    }

    /**
     * Generate a verification token for the user.
     *
     * @param  User  $user
     * @return VerificationToken
     */
    protected function generateVerificationToken(User $user): VerificationToken
    {
        $tokenString = Str::random(64);

        return VerificationToken::create([
            'user_id' => $user->id,
            'token' => $tokenString,
            'expires_at' => now()->addHours(24),
        ]);
    }
}
