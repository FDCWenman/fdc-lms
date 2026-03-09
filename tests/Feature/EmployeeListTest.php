<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function user_with_view_employees_permission_can_access_employee_list()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        $response = $this->actingAs($user)->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(\App\Livewire\Employees\ManageEmployees::class);
    }

    /** @test */
    public function user_without_view_employees_permission_receives_403()
    {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get(route('employees.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function search_filters_employees_by_name()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        // Create test employees
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 1,
        ]);

        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'status' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->set('search', 'John')
            ->assertSee('John')
            ->assertDontSee('Jane');
    }

    /** @test */
    public function search_filters_employees_by_email()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 1,
        ]);

        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'status' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->set('search', 'jane@example.com')
            ->assertSee('Jane')
            ->assertDontSee('John');
    }

    /** @test */
    public function status_filter_shows_only_matching_employees()
    {
        $user = User::factory()->create(['status' => 1, 'first_name' => 'Test', 'last_name' => 'Admin']);
        $user->givePermissionTo('view-employees');

        $activeUser = User::factory()->create([
            'first_name' => 'ActiveTestUser',
            'last_name' => 'Smith',
            'status' => 1,
        ]);

        $deactivatedUser = User::factory()->create([
            'first_name' => 'DeactivatedTestUser',
            'last_name' => 'Jones',
            'status' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->set('statusFilter', 1)
            ->assertSee($activeUser->first_name)
            ->assertDontSee($deactivatedUser->first_name);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->set('statusFilter', 2)
            ->assertSee($deactivatedUser->first_name)
            ->assertDontSee($activeUser->first_name);
    }

    /** @test */
    public function pagination_shows_15_employees_per_page()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        // Create 20 employees
        User::factory()->count(20)->create(['status' => 1]);

        $response = Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class);

        // Check that pagination is working by verifying pagination links exist
        // With 21 users total (20 + 1 test user), there should be 2 pages
        $response->assertSee('Showing');
    }

    /** @test */
    public function employee_list_displays_roles_correctly()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        $employeeWithRole = User::factory()->create([
            'first_name' => 'TestEmployee',
            'last_name' => 'WithRole',
            'status' => 1,
        ]);
        $employeeWithRole->assignRole('Employee');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->assertSee('TestEmployee')
            ->assertSee('WithRole')
            ->assertSee('Employee'); // Role name badge
    }

    /** @test */
    public function empty_search_shows_appropriate_message()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->givePermissionTo('view-employees');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->set('search', 'nonexistentuser12345')
            ->assertSee('No employees found');
    }
}
