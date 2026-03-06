<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function can_assign_permission_to_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permission = Permission::where('name', 'view-roles')->first();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', [$permission->id])
            ->call('updatePermissions')
            ->assertHasNoErrors();

        $this->assertTrue($role->fresh()->hasPermissionTo('view-roles'));
    }

    /** @test */
    public function can_assign_multiple_permissions_at_once()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permissions = Permission::whereIn('name', ['view-roles', 'create-role', 'edit-role'])->pluck('id')->toArray();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', $permissions)
            ->call('updatePermissions')
            ->assertHasNoErrors();

        $role = $role->fresh();
        $this->assertTrue($role->hasPermissionTo('view-roles'));
        $this->assertTrue($role->hasPermissionTo('create-role'));
        $this->assertTrue($role->hasPermissionTo('edit-role'));
    }

    /** @test */
    public function can_remove_permission_from_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permission1 = Permission::where('name', 'view-roles')->first();
        $permission2 = Permission::where('name', 'create-role')->first();

        $role->givePermissionTo([$permission1, $permission2]);

        // Remove one permission
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', [$permission1->id])
            ->call('updatePermissions')
            ->assertHasNoErrors();

        $role = $role->fresh();
        $this->assertTrue($role->hasPermissionTo('view-roles'));
        $this->assertFalse($role->hasPermissionTo('create-role'));
    }

    /** @test */
    public function permission_cache_cleared_after_changes()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permission = Permission::where('name', 'view-roles')->first();

        // Assign permission
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', [$permission->id])
            ->call('updatePermissions');

        // Verify cache is cleared by checking fresh permission state
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->fresh()->hasPermissionTo('view-roles'));
    }

    /** @test */
    public function audit_logs_created_for_permission_assignments()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
        $permission = Permission::where('name', 'view-roles')->first();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', [$permission->id])
            ->call('updatePermissions');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'permissions_assigned',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'causer_id' => $admin->id,
        ]);
    }

    /** @test */
    public function cannot_assign_nonexistent_permission()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->set('selectedPermissions', [999999])
            ->call('updatePermissions')
            ->assertHasErrors();
    }

    /** @test */
    public function permissions_grouped_by_category_in_ui()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\EditRole::class, ['roleId' => $role->id])
            ->assertViewHas('permissionsByCategory', function ($categories) {
                return is_array($categories) &&
                       isset($categories['Role Management']) &&
                       isset($categories['Leave Management']);
            });
    }

    /** @test */
    public function non_administrator_cannot_assign_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertStatus(403);
    }
}
