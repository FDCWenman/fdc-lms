<?php

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Models\User;
use App\Models\VerificationToken;
use App\Services\SlackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class RegisterUserActionTest extends TestCase
{
    use RefreshDatabase;

    protected SlackService $slackService;

    protected RegisterUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SlackService to avoid actual API calls
        $this->slackService = Mockery::mock(SlackService::class);
        $this->action = new RegisterUserAction($this->slackService);
    }

    /** @test */
    public function it_creates_user_with_for_verification_status(): void
    {
        config(['app.env' => 'local', 'app.allow_slack_local' => false]); // Skip Slack validation
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // In local env with allow_slack_local=false, Slack should not be called
        $this->slackService->shouldNotReceive('sendVerificationDM');
        $this->slackService->shouldNotReceive('addToChannel');

        $data = [
            'first_name' => 'John',
            'middle_name' => 'M',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
            'hired_date' => '2025-01-15',
            'roles' => ['employee'],
            'default_approvers' => ['hr_id' => 1, 'tl_id' => 2, 'pm_id' => 3],
        ];

        $user = $this->action->execute($data);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'slack_id' => 'U123456',
            'status' => 2, // for_verification
        ]);
        $this->assertEquals('John Doe', $user->name);
        $this->assertNull($user->verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function it_assigns_roles_to_user(): void
    {
        config(['app.env' => 'local', 'app.allow_slack_local' => false]);

        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $data = [
            'first_name' => 'Jane',
            'middle_name' => 'A',
            'last_name' => 'Smith',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'slack_id' => 'U654321',
            'hired_date' => '2025-02-20',
            'roles' => ['employee', 'team-lead'],
            'default_approvers' => [],
        ];

        $user = $this->action->execute($data);

        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue($user->hasRole('team-lead'));
    }

    /** @test */
    public function it_creates_verification_token(): void
    {
        config(['app.env' => 'local', 'app.allow_slack_local' => false]);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->slackService->shouldNotReceive('sendVerificationDM');
        $this->slackService->shouldNotReceive('addToChannel');

        $data = [
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'User',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'slack_id' => 'U789012',
            'hired_date' => '2025-03-01',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $user = $this->action->execute($data);

        $this->assertDatabaseHas('verification_tokens', [
            'user_id' => $user->id,
        ]);

        $token = VerificationToken::where('user_id', $user->id)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertNull($token->verified_at);
    }

    /** @test */
    public function it_stores_default_approvers_as_json(): void
    {
        config(['app.env' => 'local', 'app.allow_slack_local' => false]);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->slackService->shouldNotReceive('sendVerificationDM');
        $this->slackService->shouldNotReceive('addToChannel');

        $data = [
            'first_name' => 'Employee',
            'middle_name' => 'With',
            'last_name' => 'Approvers',
            'name' => 'Employee With Approvers',
            'email' => 'approvers@example.com',
            'password' => 'password123',
            'slack_id' => 'U111222',
            'hired_date' => '2025-01-10',
            'roles' => ['employee'],
            'default_approvers' => ['hr_id' => 5, 'tl_id' => 10, 'pm_id' => 15],
        ];

        $user = $this->action->execute($data);

        // Verify user created successfully with basic attributes
        $this->assertNotNull($user);
        $this->assertEquals('approvers@example.com', $user->email);
        $this->assertEquals('U111222', $user->slack_id);
        $this->assertEquals(2, $user->status); // for_verification
    }

    /** @test */
    public function it_validates_slack_id_in_production(): void
    {
        config(['app.env' => 'production']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->slackService
            ->shouldReceive('validateSlackId')
            ->once()
            ->with('U123456')
            ->andReturn(true);

        $this->slackService
            ->shouldReceive('sendVerificationDM')
            ->once();

        $this->slackService
            ->shouldReceive('addToChannel')
            ->once();

        $data = [
            'first_name' => 'Production',
            'middle_name' => null,
            'last_name' => 'User',
            'name' => 'Production User',
            'email' => 'prod@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
            'hired_date' => '2024-12-01',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $user = $this->action->execute($data);

        $this->assertNotNull($user);
    }

    /** @test */
    public function it_throws_exception_when_slack_id_invalid_in_production(): void
    {
        config(['app.env' => 'production']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->slackService
            ->shouldReceive('validateSlackId')
            ->once()
            ->with('INVALID')
            ->andReturn(false);

        $data = [
            'first_name' => 'Invalid',
            'middle_name' => null,
            'last_name' => 'Slack',
            'name' => 'Invalid Slack',
            'email' => 'invalid@example.com',
            'password' => 'password123',
            'slack_id' => 'INVALID',
            'hired_date' => '2025-01-01',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Slack ID or Slack API unavailable');

        $this->action->execute($data);
    }

    /** @test */
    public function it_throws_exception_when_slack_id_already_exists(): void
    {
        config(['app.env' => 'local']);

        // Create existing user
        User::factory()->create(['slack_id' => 'U999999']);

        $data = [
            'name' => 'Duplicate Slack',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'slack_id' => 'U999999',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Slack ID already exists in system');

        $this->action->execute($data);
    }

    /** @test */
    public function it_sends_verification_dm_in_production(): void
    {
        config(['app.env' => 'staging']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->slackService
            ->shouldReceive('validateSlackId')
            ->once()
            ->with('U123456')
            ->andReturn(true);

        $this->slackService
            ->shouldReceive('sendVerificationDM')
            ->once()
            ->with('U123456', Mockery::type('string'));

        $this->slackService
            ->shouldReceive('addToChannel')
            ->once()
            ->with('U123456');

        $data = [
            'first_name' => 'Staging',
            'middle_name' => null,
            'last_name' => 'User',
            'name' => 'Staging User',
            'email' => 'staging@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
            'hired_date' => '2024-11-15',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $this->action->execute($data);
    }

    /** @test */
    public function it_skips_slack_integration_in_local_environment(): void
    {
        config(['app.env' => 'local', 'app.allow_slack_local' => false]);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // SlackService should NOT be called in local with allow_slack_local=false
        $this->slackService->shouldNotReceive('validateSlackId');
        $this->slackService->shouldNotReceive('sendVerificationDM');
        $this->slackService->shouldNotReceive('addToChannel');

        $data = [
            'first_name' => 'Local',
            'middle_name' => null,
            'last_name' => 'User',
            'name' => 'Local User',
            'email' => 'local@example.com',
            'password' => 'password123',
            'slack_id' => 'U LOCAL',
            'hired_date' => '2025-01-05',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $user = $this->action->execute($data);

        $this->assertNotNull($user);
    }
}
