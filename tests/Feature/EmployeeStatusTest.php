<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view-employees']);
        Permission::create(['name' => 'manage-employees']);

        // Create HR role with manage-employees permission
        $hrRole = Role::create(['name' => 'hr']);
        $hrRole->givePermissionTo(['view-employees', 'manage-employees']);

        // Create employee role with only view permission
        $employeeRole = Role::create(['name' => 'employee']);
        $employeeRole->givePermissionTo('view-employees');
    }

    /** @test */
    public function user_with_manage_employees_permission_can_open_status_change_modal(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');

        $employee = User::factory()->create([
            'status' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $employee->id)
            ->assertSet('showStatusModal', true)
            ->assertSet('selectedUser.id', $employee->id);
    }

    /** @test */
    public function status_changes_when_confirmed_with_reason(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');

        $employee = User::factory()->create([
            'status' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $employee->id)
            ->set('statusChangeReason', 'Employee resigned')
            ->call('confirmStatusChange')
            ->assertSet('showStatusModal', false);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 2, // Deactivated
        ]);
    }

    /** @test */
    public function status_changes_when_confirmed_without_reason(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');

        $employee = User::factory()->create([
            'status' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $employee->id)
            ->set('statusChangeReason', '')
            ->call('confirmStatusChange')
            ->assertSet('showStatusModal', false);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 2, // Deactivated
        ]);
    }

    /** @test */
    public function status_unchanged_when_modal_cancelled(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');

        $employee = User::factory()->create([
            'status' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $employee->id)
            ->set('statusChangeReason', 'Some reason')
            ->set('showStatusModal', false) // Cancel modal
            ->assertSet('showStatusModal', false);

        // Status should remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 1, // Still active
        ]);
    }

    /** @test */
    public function cannot_deactivate_own_account(): void
    {
        $hr = User::factory()->create(['status' => 1]);
        $hr->assignRole('hr');

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $hr->id)
            ->assertSet('showStatusModal', false);

        // Status should remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $hr->id,
            'status' => 1, // Still active
        ]);
    }

    /** @test */
    public function activity_log_records_status_change_with_reason(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr');

        $employee = User::factory()->create([
            'status' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Livewire::actingAs($hr)
            ->test(\App\Livewire\Employees\ManageEmployees::class)
            ->call('openStatusModal', $employee->id)
            ->set('statusChangeReason', 'Employee resigned')
            ->call('confirmStatusChange');

        // Check activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $employee->id,
            'subject_type' => User::class,
            'causer_id' => $hr->id,
            'causer_type' => User::class,
            'description' => 'status_changed',
        ]);

        // Check activity log properties contain old_status, new_status, reason
        $activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $employee->id)
            ->where('description', 'status_changed')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals(1, $activity->properties['old_status']);
        $this->assertEquals(2, $activity->properties['new_status']);
        $this->assertEquals('Employee resigned', $activity->properties['reason']);
    }
}
