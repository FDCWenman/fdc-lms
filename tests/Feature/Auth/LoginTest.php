<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'employee', 'guard_name' => 'web']);
        Role::create(['name' => 'hr', 'guard_name' => 'web']);
        Role::create(['name' => 'team-lead', 'guard_name' => 'web']);
        Role::create(['name' => 'project-manager', 'guard_name' => 'web']);
    }

    /** @test */
    public function employee_can_login_and_redirect_to_leaves_page(): void
    {
        // Create an active, verified employee
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'status' => 1, // active
            'verified_at' => now(),
        ]);
        $user->assignRole('employee');

        // Attempt login
        $response = $this->post('/login', [
            'email' => 'employee@example.com',
            'password' => 'password',
        ]);

        // Assert authenticated and redirected to /leaves
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/leaves');
    }

    /** @test */
    public function approver_can_login_and_redirect_to_portal(): void
    {
        // Create an active, verified HR approver
        $user = User::factory()->create([
            'email' => 'hr@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'verified_at' => now(),
        ]);
        $user->assignRole('hr');

        $response = $this->post('/login', [
            'email' => 'hr@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/portal');
    }

    /** @test */
    public function team_lead_can_login_and_redirect_to_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'tl@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'verified_at' => now(),
        ]);
        $user->assignRole('team-lead');

        $response = $this->post('/login', [
            'email' => 'tl@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/portal');
    }

    /** @test */
    public function project_manager_can_login_and_redirect_to_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'pm@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'verified_at' => now(),
        ]);
        $user->assignRole('project-manager');

        $response = $this->post('/login', [
            'email' => 'pm@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/portal');
    }

    /** @test */
    public function unverified_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'verified_at' => null, // not verified
        ]);
        $user->assignRole('employee');

        $response = $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'status' => 0, // deactivated
            'verified_at' => now(),
        ]);
        $user->assignRole('employee');

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
            'status' => 1,
            'verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function authenticated_user_redirected_from_login_page(): void
    {
        $user = User::factory()->create([
            'status' => 1,
            'verified_at' => now(),
        ]);
        $user->assignRole('employee');

        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/leaves');
    }

    /** @test */
    public function login_page_displays_fdc_logo(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('FDCLeave Logo');
        $response->assertSee('images/fdc.png');
    }

    /** @test */
    public function login_requires_email_and_password(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function login_rate_limiting_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'verified_at' => now(),
        ]);

        // Attempt login 6 times with wrong password (rate limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // The 6th attempt should be rate limited
        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('Too many', $response->session()->get('errors')->first('email'));
    }
}
