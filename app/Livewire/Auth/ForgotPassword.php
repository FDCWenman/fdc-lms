<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\VerificationToken;
use App\Services\SlackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Forgot Password Component
 *
 * Allows users to request a password reset link via Slack DM.
 */
#[Layout('layouts.guest')]
#[Title('Forgot Password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $isSending = false;

    /**
     * Request password reset link
     */
    public function sendResetLink(SlackService $slackService): void
    {
        $this->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'We could not find an account with that email address.',
        ]);

        $this->isSending = true;

        try {
            // Find user by email
            $user = User::where('email', $this->email)->first();

            if (! $user) {
                $this->addError('email', 'We could not find an account with that email address.');

                return;
            }

            // Check if user has a Slack ID
            if (! $user->slack_id) {
                $this->addError('email', 'This account does not have a Slack ID associated. Please contact HR.');

                return;
            }

            // Generate password reset token (reuse verification_tokens table)
            $tokenString = Str::random(64);
            $token = VerificationToken::create([
                'user_id' => $user->id,
                'token' => $tokenString,
                'expires_at' => now()->addHour(), // 1 hour expiration for password reset
            ]);

            // Generate reset URL
            $resetUrl = route('password.reset', ['token' => $token->token]);

            // Send reset link via Slack DM
            $allowSlackLocal = (bool) env('ALLOW_SLACK_LOCAL', false);
            $appEnv = config('app.env');
            $shouldSendSlack = $appEnv !== 'local' || $allowSlackLocal;

            Log::info('Password reset - Slack decision', [
                'app_env' => $appEnv,
                'allow_slack_local' => $allowSlackLocal,
                'should_send_slack' => $shouldSendSlack,
            ]);

            if ($shouldSendSlack) {
                $sent = $slackService->sendPasswordResetDM($user->slack_id, $resetUrl);

                if (! $sent) {
                    Log::error('Failed to send password reset DM', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                    $this->addError('email', 'Failed to send reset link. Please try again later.');

                    return;
                }
            } else {
                // Log URL in local environment
                Log::info('Password reset URL (local environment)', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'reset_url' => $resetUrl,
                ]);
            }

            // Success message
            session()->flash('success', 'Password reset link sent via Slack DM! Check your Slack messages.');

            // Reset form
            $this->reset('email');

        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);

            $this->addError('email', 'An error occurred. Please try again later.');
        } finally {
            $this->isSending = false;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
