<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function administrator_can_view_permissions_list()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)
            ->get(route('admin.roles.permissions'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(\App\Livewire\Roles\ViewPermissions::class);
    }

    /** @test */
    public function permissions_grouped_by_category()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ViewPermissions::class)
            ->assertViewHas('permissionsByCategory', function ($categories) {
                // Should have 4 categories
                return count($categories) === 4 &&
                       isset($categories['Role Management']) &&
                       isset($categories['Leave Management']) &&
                       isset($categories['Employee Management']) &&
                       isset($categories['System Settings']);
            });
    }

    /** @test */
    public function permissions_show_name_and_description()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)
            ->get(route('admin.roles.permissions'));

        $response->assertStatus(200);
        $response->assertSee('view-roles');
        $response->assertSee('create-role');
        $response->assertSee('Role Management');
        $response->assertSee('Leave Management');
    }

    /** @test */
    public function search_filters_permissions_by_name()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ViewPermissions::class)
            ->set('search', 'leave')
            ->assertSee('view-leave-requests')
            ->assertSee('create-leave-request')
            ->assertDontSee('view-roles');
    }

    /** @test */
    public function non_administrator_cannot_view_permissions()
    {
        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $user->assignRole('Employee');

        $response = $this->actingAs($user)
            ->get(route('admin.roles.permissions'));

        $response->assertStatus(403);
    }
}
