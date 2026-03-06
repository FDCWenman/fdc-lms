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
     *     first_name: string,
     *     middle_name: ?string,
     *     last_name: string,
     *     name: string,
     *     email: string,
     *     password: string,
     *     slack_id: string,
     *     hired_date: string,
     *     roles: array<string>
     * }  $data
     * @return User
     *
     * @throws \RuntimeException When Slack API validation fails in production/staging
     */
    public function execute(array $data): User
    {
        // Validate Slack ID via API (environment-aware)
        if (config('app.env') !== 'local') {
            $isValid = $this->slackService->validateSlackId($data['slack_id']);
            if (! $isValid) {
                throw new \RuntimeException('Invalid Slack ID or Slack API unavailable');
            }
        }

        // Check for duplicate Slack ID
        if (User::where('slack_id', $data['slack_id'])->exists()) {
            throw new \RuntimeException('Slack ID already exists in system');
        }

        // Create user with "for_verification" status
        $user = User::create([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'slack_id' => $data['slack_id'],
            'hired_date' => $data['hired_date'],
            'status' => 2, // for_verification
            'verified_at' => null,
        ]);

        // Assign roles using Spatie
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        // Generate verification token
        $token = $this->generateVerificationToken($user);
        $verificationUrl = route('auth.verify', ['token' => $token->token]);

        // Send verification DM and add to Slack channel
        $allowSlackLocal = (bool) env('ALLOW_SLACK_LOCAL', false);
        $shouldSendSlack = config('app.env') !== 'local' || $allowSlackLocal;
        
        if ($shouldSendSlack) {
            $this->slackService->sendVerificationDM($data['slack_id'], $verificationUrl);
            $this->slackService->addToChannel($data['slack_id']);
        } else {
            // In local environment, log the verification URL for testing
            \Log::info('User registration verification URL (local environment)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'slack_id' => $data['slack_id'],
                'verification_url' => $verificationUrl,
            ]);
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
