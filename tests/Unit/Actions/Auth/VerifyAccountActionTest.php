<?php

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\VerifyAccountAction;
use App\Models\User;
use App\Models\VerificationToken;
use App\Services\SlackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VerifyAccountActionTest extends TestCase
{
    use RefreshDatabase;

    protected VerifyAccountAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new VerifyAccountAction();
        config(['app.env' => 'local']); // Bypass Slack by default
    }

    /** @test */
    public function it_verifies_account_with_valid_token(): void
    {
        $user = User::factory()->forVerification()->create();
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'valid-token-123',
            'expires_at' => now()->addHours(24),
        ]);

        $result = $this->action->execute('valid-token-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('Your account has been successfully verified! You can now log in.', $result['message']);
        $this->assertNotNull($result['user']);

        $user->refresh();
        $this->assertEquals(1, $user->status);
        $this->assertNotNull($user->verified_at);

        $token->refresh();
        $this->assertNotNull($token->verified_at);
    }

    /** @test */
    public function it_rejects_invalid_token(): void
    {
        $result = $this->action->execute('invalid-token');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid verification link', $result['message']);
    }

    /** @test */
    public function it_rejects_expired_token(): void
    {
        $user = User::factory()->forVerification()->create();
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'expired-token',
            'expires_at' => now()->subHours(1), // Expired 1 hour ago
        ]);

        $result = $this->action->execute('expired-token');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', $result['message']);

        $user->refresh();
        $this->assertEquals(2, $user->status); // Still for_verification
        $this->assertNull($user->verified_at);
    }

    /** @test */
    public function it_handles_already_verified_account(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()->subDays(1)]);
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'already-verified',
            'expires_at' => now()->addHours(24),
            'verified_at' => now()->subDays(1),
        ]);

        $result = $this->action->execute('already-verified');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already verified', $result['message']);
    }

    /** @test */
    public function it_requests_new_verification_link(): void
    {
        $user = User::factory()->forVerification()->create();

        $result = $this->action->requestNewVerification($user->email);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('new verification link', $result['message']);

        $this->assertDatabaseHas('verification_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_rejects_new_verification_for_nonexistent_email(): void
    {
        $result = $this->action->requestNewVerification('nonexistent@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No account found', $result['message']);
    }

    /** @test */
    public function it_rejects_new_verification_for_already_verified_user(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);

        $result = $this->action->requestNewVerification($user->email);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already verified', $result['message']);
    }

    /** @test */
    public function it_rejects_new_verification_for_deactivated_user(): void
    {
        $user = User::factory()->deactivated()->create();

        $result = $this->action->requestNewVerification($user->email);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('deactivated', $result['message']);
    }

    /** @test */
    public function it_invalidates_old_tokens_when_requesting_new_verification(): void
    {
        $user = User::factory()->forVerification()->create();

        // Create old token
        $oldToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'old-token',
            'expires_at' => now()->addHours(24),
        ]);

        // Request new verification
        $this->action->requestNewVerification($user->email);

        // Old token should be deleted
        $this->assertDatabaseMissing('verification_tokens', [
            'id' => $oldToken->id,
        ]);

        // New token should exist
        $this->assertDatabaseHas('verification_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_sends_slack_dm_in_production_when_requesting_new_verification(): void
    {
        config(['app.env' => 'production']);

        $slackService = Mockery::mock(SlackService::class);
        $slackService->shouldReceive('sendVerificationDM')
            ->once()
            ->with(Mockery::type('string'), Mockery::on(function ($url) {
                return str_contains($url, 'auth/verify');
            }));

        $this->app->instance(SlackService::class, $slackService);

        $user = User::factory()->forVerification()->create(['slack_id' => 'U123456789']);

        $result = $this->action->requestNewVerification($user->email);

        $this->assertTrue($result['success']);
    }
}
