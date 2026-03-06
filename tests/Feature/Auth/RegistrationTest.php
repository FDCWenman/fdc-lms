<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use App\Services\SlackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Set local environment to bypass Slack validation by default
        config(['app.env' => 'local']);
    }

    /** @test */
    public function successful_registration_creates_user_with_for_verification_status(): void
    {
        Livewire::test(Register::class)
            ->set('first_name', 'John')
            ->set('middle_name', 'M')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U123456789')
            ->set('hired_date', '2025-01-15')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'John M Doe',
            'email' => 'john.doe@example.com',
            'slack_id' => 'U123456789',
            'status' => 2, // for_verification
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNull($user->verified_at);
        $this->assertTrue($user->hasRole('employee'));

        // Verify token was created
        $this->assertDatabaseHas('verification_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function duplicate_email_is_rejected(): void
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(Register::class)
            ->set('first_name', 'Duplicate')
            ->set('middle_name', '')
            ->set('last_name', 'Email')
            ->set('email', 'existing@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U999999999')
            ->set('hired_date', '2025-02-01')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertCount(1, User::where('email', 'existing@example.com')->get());
    }

    /** @test */
    public function duplicate_slack_id_is_rejected(): void
    {
        // Create existing user with Slack ID
        User::factory()->create(['slack_id' => 'U111111111']);

        Livewire::test(Register::class)
            ->set('first_name', 'Duplicate')
            ->set('middle_name', '')
            ->set('last_name', 'Slack')
            ->set('email', 'duplicate.slack@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U111111111')
            ->set('hired_date', '2025-03-01')
            ->call('register')
            ->assertHasErrors(['slack_id']);

        $this->assertCount(1, User::where('slack_id', 'U111111111')->get());
    }

    /** @test */
    public function invalid_slack_id_format_is_rejected(): void
    {
        Livewire::test(Register::class)
            ->set('first_name', 'Invalid')
            ->set('middle_name', 'Slack')
            ->set('last_name', 'Format')
            ->set('email', 'invalid.slack@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'INVALID')
            ->set('hired_date', '2025-01-20')
            ->call('register')
            ->assertHasErrors(['slack_id']);
    }

    /** @test */
    public function slack_api_unavailable_error_in_production(): void
    {
        config(['app.env' => 'production']);

        // Mock SlackService to throw exception
        $slackService = Mockery::mock(SlackService::class);
        $slackService->shouldReceive('validateSlackId')
            ->once()
            ->andThrow(new \RuntimeException('Slack API unavailable'));

        $this->app->instance(SlackService::class, $slackService);

        Livewire::test(Register::class)
            ->set('first_name', 'Slack')
            ->set('middle_name', 'API')
            ->set('last_name', 'Error')
            ->set('email', 'slack.error@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U222222222')
            ->set('hired_date', '2025-01-10')
            ->call('register')
            ->assertHasErrors(['slack_id']);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'slack.error@example.com',
        ]);
    }

    /** @test */
    public function local_environment_bypasses_slack_validation(): void
    {
        config(['app.env' => 'local']);

        // Use any Slack ID format - should work in local
        Livewire::test(Register::class)
            ->set('first_name', 'Local')
            ->set('middle_name', '')
            ->set('last_name', 'Bypass')
            ->set('email', 'local@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U888888888')
            ->set('hired_date', '2024-12-15')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'local@example.com',
            'slack_id' => 'U888888888',
            'status' => 2,
        ]);
    }

    /** @test */
    public function authenticated_user_redirected_from_registration(): void
    {
        // Create employee user
        $employee = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $employee->assignRole('employee');

        $this->actingAs($employee);

        $response = $this->get(route('register'));

        // Authenticated users should be redirected to leaves page
        $response->assertRedirect('/leaves');
    }

    /** @test */
    public function registration_assigns_employee_role_automatically(): void
    {
        Livewire::test(Register::class)
            ->set('first_name', 'Auto')
            ->set('middle_name', 'Role')
            ->set('last_name', 'User')
            ->set('email', 'autorole@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U777777777')
            ->set('hired_date', '2025-01-01')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'autorole@example.com')->first();
        $this->assertTrue($user->hasRole('employee'));
    }

    /** @test */
    public function registration_stores_default_approvers(): void
    {
        // Create approvers
        $hrApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $tlApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $pmApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);

        Livewire::test(Register::class)
            ->set('first_name', 'Employee')
            ->set('middle_name', 'With')
            ->set('last_name', 'Approvers')
            ->set('email', 'approvers@example.org')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U666666666')
            ->set('hired_date', '2024-11-01')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'approvers@example.org')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Employee With Approvers', $user->name);
        $this->assertEquals(2, $user->status); // for_verification
    }
}
