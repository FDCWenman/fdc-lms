<?php

namespace Tests\Unit\Livewire;

use App\Livewire\Employees\ManageEmployees;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManageEmployeesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view-employees']);
        Permission::create(['name' => 'manage-employees']);

        // Create roles
        $hrRole = Role::create(['name' => 'hr']);
        $hrRole->givePermissionTo(['view-employees', 'manage-employees']);

        $employeeRole = Role::create(['name' => 'employee']);
        $employeeRole->givePermissionTo('view-employees');
    }

    /** @test */
    public function confirm_status_change_validates_manage_employees_permission(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee'); // Only has view permission

        $targetUser = User::factory()->create(['status' => 1]);

        Livewire::actingAs($employee)
            ->test(ManageEmployees::class)
            ->call('openStatusModal', $targetUser->id)
            ->call('confirmStatusChange')
            ->assertForbidden();
    }

    /** @test */
    public function open_status_modal_prevents_self_deactivation(): void
    {
        $hr = User::factory()->create(['status' => 1]);
        $hr->assignRole('hr');

        Livewire::actingAs($hr)
            ->test(ManageEmployees::class)
            ->call('openStatusModal', $hr->id)
            ->assertSet('showStatusModal', false)
            ->assertSet('selectedUser', null);
    }
}
