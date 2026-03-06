<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function can_view_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        activity()
            ->performedOn($role)
            ->causedBy($admin)
            ->log('created');

        $this->actingAs($admin)
            ->get(route('admin.roles.audit-logs'))
            ->assertStatus(200)
            ->assertSee('Audit Logs')
            ->assertSee('Test Role');
    }

    /** @test */
    public function audit_logs_are_paginated()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        // Create 55 audit log entries
        for ($i = 1; $i <= 55; $i++) {
            $role = Role::create([
                'name' => "Role $i",
                'guard_name' => 'web',
                'is_protected' => false,
            ]);

            activity()
                ->performedOn($role)
                ->causedBy($admin)
                ->log('created');
        }

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ViewAuditLogs::class)
            ->assertSee('Role 1')
            ->assertSee('Role 50')
            ->assertDontSee('Role 51'); // Should be on page 2
    }

    /** @test */
    public function can_filter_by_action_type()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $role1 = Role::create([
            'name' => 'Created Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        $role2 = Role::create([
            'name' => 'Updated Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        activity()
            ->performedOn($role1)
            ->causedBy($admin)
            ->log('created');

        activity()
            ->performedOn($role2)
            ->causedBy($admin)
            ->log('updated');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ViewAuditLogs::class)
            ->set('actionFilter', 'created')
            ->assertSee('Created Role')
            ->assertDontSee('Updated Role');
    }

    /** @test */
    public function can_filter_by_user()
    {
        $admin1 = User::factory()->create(['name' => 'Admin One']);
        $admin1->assignRole('Administrator');

        $admin2 = User::factory()->create(['name' => 'Admin Two']);
        $admin2->assignRole('Administrator');

        $role1 = Role::create([
            'name' => 'Role by Admin1',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        $role2 = Role::create([
            'name' => 'Role by Admin2',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        activity()
            ->performedOn($role1)
            ->causedBy($admin1)
            ->log('created');

        activity()
            ->performedOn($role2)
            ->causedBy($admin2)
            ->log('created');

        \Livewire\Livewire::actingAs($admin1)
            ->test(\App\Livewire\Roles\ViewAuditLogs::class)
            ->set('userFilter', $admin1->id)
            ->assertSee('Role by Admin1')
            ->assertDontSee('Role by Admin2');
    }

    /** @test */
    public function can_filter_by_date_range()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $oldRole = Role::create([
            'name' => 'Old Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        activity()
            ->performedOn($oldRole)
            ->causedBy($admin)
            ->createdAt(now()->subDays(10))
            ->log('created');

        $newRole = Role::create([
            'name' => 'New Role',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        activity()
            ->performedOn($newRole)
            ->causedBy($admin)
            ->log('created');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Roles\ViewAuditLogs::class)
            ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
            ->assertSee('New Role')
            ->assertDontSee('Old Role');
    }
}
