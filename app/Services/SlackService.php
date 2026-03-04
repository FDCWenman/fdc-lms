<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SlackService
{
    protected Client $client;
    protected bool $enabled;
    protected string $botToken;
    protected ?string $webhookUrl;
    protected ?string $leaveChannelId;
    protected int $timeout;

    public function __construct()
    {
        $this->enabled = config('services.slack_api.enabled', false);
        $this->botToken = config('services.slack_api.bot_token', '');
        $this->webhookUrl = config('services.slack_api.webhook_url');
        $this->leaveChannelId = config('services.slack_api.leave_channel_id');
        $this->timeout = config('services.slack_api.timeout', 10);

        $this->client = new Client([
            'base_uri' => 'https://slack.com/api/',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->botToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send a direct message to a Slack user.
     *
     * @param string $slackId The Slack user ID (e.g., U1234567890)
     * @param string $message The message text to send
     * @return array{success: bool, error?: string, channel?: string}
     */
    public function sendDirectMessage(string $slackId, string $message): array
    {
        if (!$this->enabled) {
            Log::info('Slack is disabled. Message not sent.', ['slack_id' => $slackId]);
            return ['success' => false, 'error' => 'Slack integration is disabled'];
        }

        try {
            // First, open a DM channel with the user
            $response = $this->client->post('conversations.open', [
                'json' => ['users' => $slackId],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['ok']) {
                Log::error('Failed to open Slack DM channel', [
                    'slack_id' => $slackId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'error' => $data['error'] ?? 'Failed to open DM'];
            }

            $channelId = $data['channel']['id'];

            // Send the message to the DM channel
            $response = $this->client->post('chat.postMessage', [
                'json' => [
                    'channel' => $channelId,
                    'text' => $message,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['ok']) {
                Log::error('Failed to send Slack message', [
                    'slack_id' => $slackId,
                    'channel' => $channelId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'error' => $data['error'] ?? 'Failed to send message'];
            }

            Log::info('Slack DM sent successfully', [
                'slack_id' => $slackId,
                'channel' => $channelId,
            ]);

            return ['success' => true, 'channel' => $channelId];

        } catch (GuzzleException $e) {
            Log::error('Slack API request failed', [
                'slack_id' => $slackId,
                'exception' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Network error: ' . $e->getMessage()];
        }
    }

    /**
     * Validate that a Slack user ID exists.
     *
     * @param string $slackId The Slack user ID to validate
     * @return array{valid: bool, error?: string, user?: array}
     */
    public function validateSlackId(string $slackId): array
    {
        if (!$this->enabled) {
            return ['valid' => false, 'error' => 'Slack integration is disabled'];
        }

        try {
            $response = $this->client->get('users.info', [
                'query' => ['user' => $slackId],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['ok']) {
                Log::warning('Invalid Slack user ID', [
                    'slack_id' => $slackId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['valid' => false, 'error' => $data['error'] ?? 'Invalid Slack ID'];
            }

            return [
                'valid' => true,
                'user' => [
                    'id' => $data['user']['id'],
                    'name' => $data['user']['real_name'] ?? $data['user']['name'],
                    'email' => $data['user']['profile']['email'] ?? null,
                ],
            ];

        } catch (GuzzleException $e) {
            Log::error('Slack user validation failed', [
                'slack_id' => $slackId,
                'exception' => $e->getMessage(),
            ]);
            return ['valid' => false, 'error' => 'Network error: ' . $e->getMessage()];
        }
    }

    /**
     * Add a user to a Slack channel.
     *
     * @param string $slackId The Slack user ID
     * @param string|null $channelId The channel ID (defaults to leave channel)
     * @return array{success: bool, error?: string}
     */
    public function addToChannel(string $slackId, ?string $channelId = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'Slack integration is disabled'];
        }

        $channelId = $channelId ?? $this->leaveChannelId;

        if (!$channelId) {
            return ['success' => false, 'error' => 'No channel ID provided'];
        }

        try {
            $response = $this->client->post('conversations.invite', [
                'json' => [
                    'channel' => $channelId,
                    'users' => $slackId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['ok']) {
                // User might already be in the channel - check for that specific error
                if ($data['error'] === 'already_in_channel') {
                    return ['success' => true];
                }

                Log::error('Failed to add user to Slack channel', [
                    'slack_id' => $slackId,
                    'channel' => $channelId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'error' => $data['error'] ?? 'Failed to add to channel'];
            }

            Log::info('User added to Slack channel', [
                'slack_id' => $slackId,
                'channel' => $channelId,
            ]);

            return ['success' => true];

        } catch (GuzzleException $e) {
            Log::error('Failed to add user to Slack channel', [
                'slack_id' => $slackId,
                'channel' => $channelId,
                'exception' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Network error: ' . $e->getMessage()];
        }
    }

    /**
     * Get the display name of a Slack user.
     *
     * @param string $slackId The Slack user ID
     * @return array{success: bool, name?: string, error?: string}
     */
    public function getDisplayName(string $slackId): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'Slack integration is disabled'];
        }

        try {
            $response = $this->client->get('users.profile.get', [
                'query' => ['user' => $slackId],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['ok']) {
                return ['success' => false, 'error' => $data['error'] ?? 'Failed to get profile'];
            }

            $displayName = $data['profile']['display_name'] 
                ?? $data['profile']['real_name'] 
                ?? $data['profile']['name']
                ?? 'Unknown';

            return ['success' => true, 'name' => $displayName];

        } catch (GuzzleException $e) {
            Log::error('Failed to get Slack display name', [
                'slack_id' => $slackId,
                'exception' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Network error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if Slack integration is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
