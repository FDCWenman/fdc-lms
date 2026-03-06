<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function employee_role_redirects_to_leaves(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get('/leaves');

        $response->assertOk();
        $response->assertSee('Leave Management');
    }

    /** @test */
    public function hr_role_redirects_to_portal(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        $user->assignRole('hr');

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertSee('Approval Portal');
    }

    /** @test */
    public function team_lead_role_redirects_to_portal(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        $user->assignRole('team-lead');

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertSee('Approval Portal');
    }

    /** @test */
    public function project_manager_role_redirects_to_portal(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        $user->assignRole('project-manager');

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertSee('Approval Portal');
    }

    /** @test */
    public function multi_role_user_has_access_to_all_role_capabilities(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        $user->assignRole(['employee', 'team-lead']);

        // Can access employee pages
        $response = $this->actingAs($user)->get('/leaves');
        $response->assertOk();

        // Can also access approver pages
        $response = $this->actingAs($user)->get('/portal');
        $response->assertOk();
    }

    /** @test */
    public function unauthorized_role_access_is_blocked(): void
    {
        $employee = User::factory()->active()->create(['verified_at' => now()]);
        $employee->assignRole('employee');

        // Employee trying to access portal should be blocked
        $response = $this->actingAs($employee)->get('/portal');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_redirects_to_login(): void
    {
        $response = $this->get('/leaves');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_without_role_cannot_access_protected_routes(): void
    {
        $user = User::factory()->active()->create(['verified_at' => now()]);
        // No role assigned

        $response = $this->actingAs($user)->get('/leaves');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/portal');
        $response->assertStatus(403);
    }

    /** @test */
    public function login_redirects_employee_to_leaves(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'employee@example.com',
            'password' => bcrypt('password123'),
            'verified_at' => now(),
        ]);
        $user->assignRole('employee');

        $response = $this->post('/login', [
            'email' => 'employee@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/leaves');
    }

    /** @test */
    public function login_redirects_approver_to_portal(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'hr@example.com',
            'password' => bcrypt('password123'),
            'verified_at' => now(),
        ]);
        $user->assignRole('hr');

        $response = $this->post('/login', [
            'email' => 'hr@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/portal');
    }
}
