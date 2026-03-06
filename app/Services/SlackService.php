<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    protected string $botToken;

    protected string $channelId;

    protected string $webhookUrl;

    protected bool $isLocal;

    protected bool $allowSlackLocal;

    public function __construct()
    {
        $this->botToken = config('slack.bot_token', '');
        $this->channelId = config('slack.channel_id', '');
        $this->webhookUrl = config('slack.webhook_url', '');
        $this->isLocal = app()->environment('local');
        $this->allowSlackLocal = (bool) env('ALLOW_SLACK_LOCAL', false);
    }

    /**
     * Validate if a Slack ID exists and is valid.
     * In local environment, bypass actual API call unless ALLOW_SLACK_LOCAL=1.
     */
    public function validateSlackId(string $slackId): bool
    {
        if ($this->isLocal && ! $this->allowSlackLocal) {
            Log::info('Slack API bypassed in local environment', ['slack_id' => $slackId]);

            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->botToken,
            ])->get('https://slack.com/api/users.info', [
                'user' => $slackId,
            ]);

            $data = $response->json();

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('Slack ID validation failed', [
                'slack_id' => $slackId,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Slack API unavailable. Please try again later.');
        }
    }

    /**
     * Add a user to the Slack leave management channel.
     * In local environment, skip actual API call.
     */
    public function addToChannel(string $slackId): bool
    {
        if ($this->isLocal) {
            Log::info('Slack channel invitation bypassed in local environment', ['slack_id' => $slackId]);

            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->botToken,
            ])->post('https://slack.com/api/conversations.invite', [
                'channel' => $this->channelId,
                'users' => $slackId,
            ]);

            $data = $response->json();

            if (! ($data['ok'] ?? false)) {
                Log::warning('Failed to add user to Slack channel', [
                    'slack_id' => $slackId,
                    'error' => $data['error'] ?? 'unknown',
                ]);
            }

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('Slack channel invitation failed', [
                'slack_id' => $slackId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a verification DM to the user via Slack.
     * In local environment, skip actual API call.
     */
    public function sendVerificationDM(string $slackId, string $verificationUrl): bool
    {
        if ($this->isLocal) {
            Log::info('Slack DM bypassed in local environment', [
                'slack_id' => $slackId,
                'url' => $verificationUrl,
            ]);

            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->botToken,
            ])->post('https://slack.com/api/chat.postMessage', [
                'channel' => $slackId,
                'text' => "Welcome to FDCLeave! Please verify your account by clicking the link below:\n\n{$verificationUrl}\n\nThis link will expire in 24 hours.",
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Welcome to FDCLeave!*\n\nPlease verify your account to get started.",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' => [
                            [
                                'type' => 'button',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => 'Verify Account',
                                ],
                                'url' => $verificationUrl,
                                'style' => 'primary',
                            ],
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => '_This link will expire in 24 hours._',
                            ],
                        ],
                    ],
                ],
            ]);

            $data = $response->json();

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('Slack verification DM failed', [
                'slack_id' => $slackId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a password reset DM to the user via Slack.
     * In local environment, skip actual API call.
     */
    public function sendPasswordResetDM(string $slackId, string $resetUrl): bool
    {
        if ($this->isLocal && ! $this->allowSlackLocal) {
            Log::info('Slack password reset DM bypassed in local environment', [
                'slack_id' => $slackId,
                'url' => $resetUrl,
            ]);

            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->botToken,
            ])->post('https://slack.com/api/chat.postMessage', [
                'channel' => $slackId,
                'text' => "You requested to reset your FDCLeave password. Click the link below to reset it:\n\n{$resetUrl}\n\nThis link will expire in 1 hour. If you didn't request this, please ignore this message.",
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Password Reset Request*\n\nYou requested to reset your FDCLeave password.",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' => [
                            [
                                'type' => 'button',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => 'Reset Password',
                                ],
                                'url' => $resetUrl,
                                'style' => 'danger',
                            ],
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "_This link will expire in 1 hour. If you didn't request this, please ignore this message._",
                            ],
                        ],
                    ],
                ],
            ]);

            $data = $response->json();

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('Slack password reset DM failed', [
                'slack_id' => $slackId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
