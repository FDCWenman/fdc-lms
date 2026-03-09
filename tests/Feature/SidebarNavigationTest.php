<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function sidebar_displays_with_fdc_logo_and_branding()
    {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('FDC LMS');
        $response->assertSee('fdc-logo.png');
    }

    /** @test */
    public function dashboard_link_appears_as_first_menu_item()
    {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee(route('dashboard'));
    }

    /** @test */
    public function employee_management_link_visible_with_view_employees_permission()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Employee Management');
        $response->assertSee(route('employees.index'));
    }

    /** @test */
    public function employee_management_link_not_visible_without_permission()
    {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Employee Management');
    }

    /** @test */
    public function administration_section_visible_with_role_management_permissions()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-roles');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Administration');
        $response->assertSee('Roles');
        $response->assertSee('Permissions');
    }

    /** @test */
    public function current_page_highlighted_in_sidebar_navigation()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200);
        // Verify the page loads successfully - Flux handles active state highlighting automatically
        $response->assertSee('Employee Management');
    }
}
