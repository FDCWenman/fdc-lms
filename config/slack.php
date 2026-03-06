<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slack API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Slack integration used in authentication and
    | notifications. Bot token is required for API calls, channel ID for
    | invitations, and webhook URL for notifications.
    |
    */

    'bot_token' => env('SLACK_BOT_TOKEN', ''),
    'channel_id' => env('SLACK_CHANNEL_ID', ''),
    'webhook_url' => env('SLACK_WEBHOOK_URL', ''),

];
