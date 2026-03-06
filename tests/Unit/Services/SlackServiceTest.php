<?php

namespace Tests\Unit\Services;

use App\Services\SlackService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackServiceTest extends TestCase
{
    protected SlackService $slackService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slackService = new SlackService;
    }

    /** @test */
    public function validates_slack_id_successfully_in_production(): void
    {
        // Set environment to production
        app()->instance('env', 'production');

        Http::fake([
            'slack.com/api/users.info*' => Http::response([
                'ok' => true,
                'user' => [
                    'id' => 'U12345678',
                    'name' => 'Test User',
                ],
            ], 200),
        ]);

        $result = $this->slackService->validateSlackId('U12345678');

        $this->assertTrue($result);
    }

    /** @test */
    public function validates_slack_id_returns_false_for_invalid_id(): void
    {
        app()->instance('env', 'production');

        Http::fake([
            'slack.com/api/users.info*' => Http::response([
                'ok' => false,
                'error' => 'user_not_found',
            ], 200),
        ]);

        $result = $this->slackService->validateSlackId('INVALID');

        $this->assertFalse($result);
    }

    /** @test */
    public function validates_slack_id_bypasses_in_local_environment(): void
    {
        // Ensure we're in local environment
        config(['app.env' => 'local']);

        // No HTTP call should be made
        Http::fake();

        $result = $this->slackService->validateSlackId('U12345678');

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function adds_user_to_channel_successfully(): void
    {
        app()->instance('env', 'production');

        Http::fake([
            'slack.com/api/conversations.invite*' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $result = $this->slackService->addToChannel('U12345678');

        $this->assertTrue($result);
    }

    /** @test */
    public function adds_user_to_channel_bypasses_in_local_environment(): void
    {
        config(['app.env' => 'local']);

        Http::fake();

        $result = $this->slackService->addToChannel('U12345678');

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function sends_verification_dm_successfully(): void
    {
        app()->instance('env', 'production');

        Http::fake([
            'slack.com/api/chat.postMessage*' => Http::response([
                'ok' => true,
                'ts' => '1234567890.123456',
            ], 200),
        ]);

        $result = $this->slackService->sendVerificationDM('U12345678', 'https://example.com/verify/token123');

        $this->assertTrue($result);
    }

    /** @test */
    public function sends_verification_dm_bypasses_in_local_environment(): void
    {
        config(['app.env' => 'local']);

        Http::fake();

        $result = $this->slackService->sendVerificationDM('U12345678', 'https://example.com/verify/token123');

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    /** @test */
    public function throws_exception_when_slack_api_is_unavailable(): void
    {
        app()->instance('env', 'production');

        Http::fake([
            'slack.com/api/users.info*' => Http::response('Service Unavailable', 503),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Slack API unavailable');

        $this->slackService->validateSlackId('U12345678');
    }
}
