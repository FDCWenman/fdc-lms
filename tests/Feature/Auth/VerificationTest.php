<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\RequestNewVerification;
use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set local environment to bypass Slack
        config(['app.env' => 'local']);
    }

    /** @test */
    public function successful_verification_activates_account(): void
    {
        $user = User::factory()->forVerification()->create();
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'valid-token-123',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->get(route('auth.verify', ['token' => 'valid-token-123']));

        $response->assertOk();
        $response->assertSee('Verification Successful');
        $response->assertSee('Go to Login');

        $user->refresh();
        $this->assertEquals(1, $user->status);
        $this->assertNotNull($user->verified_at);

        $token->refresh();
        $this->assertNotNull($token->verified_at);
    }

    /** @test */
    public function expired_token_shows_error(): void
    {
        $user = User::factory()->forVerification()->create();
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'expired-token',
            'expires_at' => now()->subHours(1),
        ]);

        $response = $this->get(route('auth.verify', ['token' => 'expired-token']));

        $response->assertOk();
        $response->assertSee('Verification Failed');
        $response->assertSee('expired');
        $response->assertSee('Request New Verification Link');

        $user->refresh();
        $this->assertEquals(2, $user->status); // Still for_verification
        $this->assertNull($user->verified_at);
    }

    /** @test */
    public function invalid_token_shows_error(): void
    {
        $response = $this->get(route('auth.verify', ['token' => 'invalid-token']));

        $response->assertOk();
        $response->assertSee('Verification Failed');
        $response->assertSee('Invalid verification link');
    }

    /** @test */
    public function already_verified_account_shows_success(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()->subDays(1)]);
        $token = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'already-verified',
            'expires_at' => now()->addHours(24),
            'verified_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('auth.verify', ['token' => 'already-verified']));

        $response->assertOk();
        $response->assertSee('Verification Successful');
        $response->assertSee('already verified');
    }

    /** @test */
    public function user_can_request_new_verification_link(): void
    {
        $user = User::factory()->forVerification()->create();

        Livewire::test(RequestNewVerification::class)
            ->set('email', $user->email)
            ->call('requestVerification')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('verification_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function requesting_new_verification_for_nonexistent_email_shows_error(): void
    {
        Livewire::test(RequestNewVerification::class)
            ->set('email', 'nonexistent@example.com')
            ->call('requestVerification')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function requesting_new_verification_for_verified_account_shows_error(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);

        Livewire::test(RequestNewVerification::class)
            ->set('email', $user->email)
            ->call('requestVerification')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function requesting_new_verification_for_deactivated_account_shows_error(): void
    {
        $user = User::factory()->deactivated()->create();

        Livewire::test(RequestNewVerification::class)
            ->set('email', $user->email)
            ->call('requestVerification')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function requesting_new_verification_invalidates_old_tokens(): void
    {
        $user = User::factory()->forVerification()->create();

        // Create old token
        $oldToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'old-token',
            'expires_at' => now()->addHours(24),
        ]);

        Livewire::test(RequestNewVerification::class)
            ->set('email', $user->email)
            ->call('requestVerification')
            ->assertHasNoErrors();

        // Old token should be deleted
        $this->assertDatabaseMissing('verification_tokens', [
            'id' => $oldToken->id,
        ]);

        // New token should exist
        $newToken = VerificationToken::where('user_id', $user->id)->first();
        $this->assertNotNull($newToken);
        $this->assertNotEquals($oldToken->token, $newToken->token);
    }

    /** @test */
    public function verified_user_can_login(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'verified@example.com',
            'password' => bcrypt('password123'),
            'verified_at' => now(),
        ]);

        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $user->assignRole('employee');

        $response = $this->post('/login', [
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
    }

    /** @test */
    public function verification_page_is_accessible(): void
    {
        $response = $this->get(route('auth.request-verification'));

        $response->assertOk();
        $response->assertSee('Request New Verification Link');
    }
}
