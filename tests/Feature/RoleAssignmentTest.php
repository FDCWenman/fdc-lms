<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function can_assign_role_to_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $role = Role::where('name', 'Manager')->first();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $user->id])
            ->set('selectedRoles', [$role->id])
            ->call('updateRoles')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasRole('Manager'));
    }

    /** @test */
    public function can_assign_multiple_roles_to_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $roles = Role::whereIn('name', ['Manager', 'Employee'])->pluck('id')->toArray();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $user->id])
            ->set('selectedRoles', $roles)
            ->call('updateRoles')
            ->assertHasNoErrors();

        $user = $user->fresh();
        $this->assertTrue($user->hasRole('Manager'));
        $this->assertTrue($user->hasRole('Employee'));
    }

    /** @test */
    public function can_remove_role_from_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $user->assignRole(['Manager', 'Employee']);

        $managerRole = Role::where('name', 'Manager')->first();

        // Remove Employee role, keep Manager
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $user->id])
            ->set('selectedRoles', [$managerRole->id])
            ->call('updateRoles')
            ->assertHasNoErrors();

        $user = $user->fresh();
        $this->assertTrue($user->hasRole('Manager'));
        $this->assertFalse($user->hasRole('Employee'));
    }

    /** @test */
    public function user_gains_combined_permissions_from_all_roles()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $roles = Role::whereIn('name', ['Manager', 'Employee'])->pluck('id')->toArray();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $user->id])
            ->set('selectedRoles', $roles)
            ->call('updateRoles');

        $user = $user->fresh();

        // Manager role permissions
        $this->assertTrue($user->hasPermissionTo('approve-leave-requests'));
        $this->assertTrue($user->hasPermissionTo('view-employees'));

        // Employee role permissions
        $this->assertTrue($user->hasPermissionTo('view-leave-requests'));
        $this->assertTrue($user->hasPermissionTo('create-leave-request'));
    }

    /** @test */
    public function cannot_remove_administrator_role_from_last_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        // Try to remove Administrator role from the only admin
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $admin->id])
            ->set('selectedRoles', [])
            ->call('updateRoles')
            ->assertHasErrors();

        // Verify admin still has Administrator role
        $this->assertTrue($admin->fresh()->hasRole('Administrator'));
    }

    /** @test */
    public function can_remove_administrator_role_if_multiple_admins_exist()
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('Administrator');

        $admin2 = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $admin2->assignRole('Administrator');

        $employeeRole = Role::where('name', 'Employee')->first();

        // Remove Administrator from admin2 (admin1 still exists)
        \Livewire\Livewire::actingAs($admin1)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $admin2->id])
            ->set('selectedRoles', [$employeeRole->id])
            ->call('updateRoles')
            ->assertHasNoErrors();

        $admin2 = $admin2->fresh();
        $this->assertFalse($admin2->hasRole('Administrator'));
        $this->assertTrue($admin2->hasRole('Employee'));
    }

    /** @test */
    public function audit_logs_created_for_role_assignments()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $role = Role::where('name', 'Manager')->first();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\AssignRoles::class, ['userId' => $user->id])
            ->set('selectedRoles', [$role->id])
            ->call('updateRoles');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'roles_assigned',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_id' => $admin->id,
        ]);
    }

    /** @test */
    public function non_administrator_cannot_assign_roles()
    {
        $user = User::factory()->create(['status' => 1, 'verified_at' => now()]);
        $user->assignRole('Employee');

        $targetUser = User::factory()->create(['status' => 1, 'verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertStatus(403);
    }
}
