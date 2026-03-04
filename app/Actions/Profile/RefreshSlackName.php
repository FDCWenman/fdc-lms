<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Services\SlackService;

class RefreshSlackName
{
    public function __construct(
        protected SlackService $slackService
    ) {}

    /**
     * Refresh a user's name from Slack.
     */
    public function execute(User $user): array
    {
        if (!$user->slack_id) {
            return [
                'success' => false,
                'message' => 'User does not have a Slack ID configured.',
            ];
        }

        $result = $this->slackService->getDisplayName($user->slack_id);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to fetch Slack display name: ' . ($result['error'] ?? 'Unknown error'),
            ];
        }

        // Update user name if different
        if ($user->name !== $result['name']) {
            $user->update(['name' => $result['name']]);
        }

        return [
            'success' => true,
            'name' => $result['name'],
            'message' => 'Display name refreshed successfully.',
        ];
    }
}
