<?php

namespace App\Livewire\Roles;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class AssignRoles extends Component
{
    use WithPagination;

    public $userId;

    public $user;

    public $selectedRoles = [];

    public $roles;

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->user = User::findOrFail($userId);

        // Load current roles
        $this->selectedRoles = $this->user->roles->pluck('id')->toArray();

        // Load all available roles
        $this->roles = Role::all();
    }

    public function updateRoles()
    {
        // Check if removing Administrator role from last admin
        if ($this->isRemovingLastAdministrator()) {
            $this->addError('selectedRoles', 'Cannot remove Administrator role. At least one administrator must exist.');

            return;
        }

        // Validate selected roles exist
        $this->validate([
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,id',
        ]);

        // Get old roles for audit log
        $oldRoles = $this->user->roles->pluck('name')->toArray();

        // Sync roles
        $this->user->syncRoles(
            Role::whereIn('id', $this->selectedRoles)->get()
        );

        // Get new roles for audit log
        $newRoles = $this->user->fresh()->roles->pluck('name')->toArray();

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Log activity
        activity()
            ->performedOn($this->user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
            ])
            ->log('roles_assigned');

        session()->flash('message', 'Roles updated successfully.');

        // Refresh user data
        $this->user = $this->user->fresh();
    }

    protected function isRemovingLastAdministrator()
    {
        // Check if Administrator role is currently assigned to this user
        $administratorRole = Role::where('name', 'Administrator')->first();

        if (! $administratorRole || ! $this->user->hasRole('Administrator')) {
            return false;
        }

        // Check if Administrator is being removed (not in selected roles)
        if (in_array($administratorRole->id, $this->selectedRoles)) {
            return false;
        }

        // Count total administrators in the system
        $adminCount = User::role('Administrator')->count();

        // If this is the last admin, prevent removal
        return $adminCount <= 1;
    }

    public function render()
    {
        return view('livewire.roles.assign-roles');
    }
}
