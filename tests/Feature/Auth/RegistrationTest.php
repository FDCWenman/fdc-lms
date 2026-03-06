<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use App\Models\VerificationToken;
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
        parent->setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Set local environment to bypass Slack validation by default
        config(['app.env' => 'local']);
    }

    /** @test */
    public function successful_registration_creates_user_with_for_verification_status(): void
    {
        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'John Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U123456789')
            ->set('roles', ['employee'])
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users_fdc_leaves', [
            'name' => 'John Doe',
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

        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Duplicate Email')
            ->set('email', 'existing@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U999999999')
            ->set('roles', ['employee'])
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertCount(1, User::where('email', 'existing@example.com')->get());
    }

    /** @test */
    public function duplicate_slack_id_is_rejected(): void
    {
        // Create existing user with Slack ID
        User::factory()->create(['slack_id' => 'U111111111']);

        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Duplicate Slack')
            ->set('email', 'duplicate.slack@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U111111111')
            ->set('roles', ['employee'])
            ->call('register')
            ->assertHasErrors(['slack_id']);

        $this->assertCount(1, User::where('slack_id', 'U111111111')->get());
    }

    /** @test */
    public function invalid_slack_id_format_is_rejected(): void
    {
        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Invalid Slack Format')
            ->set('email', 'invalid.slack@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'INVALID')
            ->set('roles', ['employee'])
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

        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Slack API Error')
            ->set('email', 'slack.error@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U222222222')
            ->set('roles', ['employee'])
            ->call('register')
            ->assertHasErrors(['slack_id']);

        // User should not be created
        $this->assertDatabaseMissing('users_fdc_leaves', [
            'email' => 'slack.error@example.com',
        ]);
    }

    /** @test */
    public function local_environment_bypasses_slack_validation(): void
    {
        config(['app.env' => 'local']);

        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        // Use any Slack ID format - should work in local
        Livewire::test(Register::class)
            ->set('name', 'Local Bypass')
            ->set('email', 'local@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U888888888')
            ->set('roles', ['employee'])
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users_fdc_leaves', [
            'email' => 'local@example.com',
            'slack_id' => 'U888888888',
            'status' => 2,
        ]);
    }

    /** @test */
    public function non_hr_user_is_blocked_from_registration(): void
    {
        // Create employee user (not HR)
        $employee = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $employee->assignRole('employee');

        $this->actingAs($employee);

        $response = $this->get(route('register'));

        $response->assertStatus(403);
    }

    /** @test */
    public function registration_assigns_multiple_roles(): void
    {
        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Multi Role User')
            ->set('email', 'multirole@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U777777777')
            ->set('roles', ['employee', 'team-lead'])
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'multirole@example.com')->first();
        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue($user->hasRole('team-lead'));
    }

    /** @test */
    public function registration_stores_default_approvers(): void
    {
        // Create approvers
        $hrApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $tlApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $pmApprover = User::factory()->create(['status' => 1, 'verified_at' => now()]);

        // Create HR admin
        $hrAdmin = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $hrAdmin->assignRole('hr');

        $this->actingAs($hrAdmin);

        Livewire::test(Register::class)
            ->set('name', 'Employee With Approvers')
            ->set('email', 'approvers@example.com')
            ->set('password', 'Test@12345')
            ->set('password_confirmation', 'Test@12345')
            ->set('slack_id', 'U666666666')
            ->set('roles', ['employee'])
            ->set('hr_approver_id', $hrApprover->id)
            ->set('tl_approver_id', $tlApprover->id)
            ->set('pm_approver_id', $pmApprover->id)
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'approvers@example.com')->first();
        $this->assertEquals([
            'hr_id' => $hrApprover->id,
            'tl_id' => $tlApprover->id,
            'pm_id' => $pmApprover->id,
        ], $user->default_approvers);
    }
}
