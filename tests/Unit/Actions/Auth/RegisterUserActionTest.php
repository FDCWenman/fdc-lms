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
        config(['app.env' => 'local']); // Skip Slack validation

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
            'roles' => ['employee'],
            'default_approvers' => ['hr_id' => 1, 'tl_id' => 2, 'pm_id' => 3],
        ];

        $user = $this->action->execute($data);

        $this->assertDatabaseHas('users_fdc_leaves', [
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
        config(['app.env' => 'local']);

        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $data = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'slack_id' => 'U654321',
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
        config(['app.env' => 'local']);

        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'slack_id' => 'U789012',
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
        config(['app.env' => 'local']);

        $data = [
            'name' => 'Employee With Approvers',
            'email' => 'approvers@example.com',
            'password' => 'password123',
            'slack_id' => 'U111222',
            'roles' => ['employee'],
            'default_approvers' => ['hr_id' => 5, 'tl_id' => 10, 'pm_id' => 15],
        ];

        $user = $this->action->execute($data);

        $this->assertEquals(['hr_id' => 5, 'tl_id' => 10, 'pm_id' => 15], $user->default_approvers);
    }

    /** @test */
    public function it_validates_slack_id_in_production(): void
    {
        config(['app.env' => 'production']);

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
            'name' => 'Production User',
            'email' => 'prod@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
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

        $this->slackService
            ->shouldReceive('validateSlackId')
            ->once()
            ->with('INVALID')
            ->andReturn(false);

        $data = [
            'name' => 'Invalid Slack',
            'email' => 'invalid@example.com',
            'password' => 'password123',
            'slack_id' => 'INVALID',
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

        $this->slackService
            ->shouldReceive('validateSlackId')
            ->once()
            ->andReturn(true);

        $this->slackService
            ->shouldReceive('sendVerificationDM')
            ->once()
            ->with('U123456', Mockery::on(function ($url) {
                return str_contains($url, route('auth.verify', ['token' => 'dummy'], false));
            }));

        $this->slackService
            ->shouldReceive('addToChannel')
            ->once()
            ->with('U123456');

        $data = [
            'name' => 'Staging User',
            'email' => 'staging@example.com',
            'password' => 'password123',
            'slack_id' => 'U123456',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $this->action->execute($data);
    }

    /** @test */
    public function it_skips_slack_integration_in_local_environment(): void
    {
        config(['app.env' => 'local']);

        // SlackService should NOT be called in local
        $this->slackService->shouldNotReceive('validateSlackId');
        $this->slackService->shouldNotReceive('sendVerificationDM');
        $this->slackService->shouldNotReceive('addToChannel');

        $data = [
            'name' => 'Local User',
            'email' => 'local@example.com',
            'password' => 'password123',
            'slack_id' => 'U LOCAL',
            'roles' => ['employee'],
            'default_approvers' => [],
        ];

        $user = $this->action->execute($data);

        $this->assertNotNull($user);
    }
}
