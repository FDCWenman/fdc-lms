<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EditRole extends Component
{
    public $roleId;

    public $role;

    public $selectedPermissions = [];

    public $permissionsByCategory = [];

    public function mount($roleId)
    {
        $this->roleId = $roleId;
        $this->role = Role::findOrFail($roleId);

        // Load current permissions
        $this->selectedPermissions = $this->role->permissions->pluck('id')->toArray();

        // Group permissions by category
        $this->loadPermissions();
    }

    protected function loadPermissions()
    {
        $permissions = Permission::all();

        $this->permissionsByCategory = $permissions->groupBy('category')->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                ];
            });
        })->toArray();
    }

    public function updatePermissions()
    {
        // Validate selected permissions exist
        $this->validate([
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'exists:permissions,id',
        ]);

        // Get old permissions for audit log
        $oldPermissions = $this->role->permissions->pluck('name')->toArray();

        // Sync permissions
        $this->role->syncPermissions(
            Permission::whereIn('id', $this->selectedPermissions)->get()
        );

        // Get new permissions for audit log
        $newPermissions = $this->role->fresh()->permissions->pluck('name')->toArray();

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Log activity
        activity()
            ->performedOn($this->role)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_permissions' => $oldPermissions,
                'new_permissions' => $newPermissions,
            ])
            ->log('permissions_assigned');

        session()->flash('message', 'Permissions updated successfully.');
    }

    public function selectAll()
    {
        $this->selectedPermissions = Permission::all()->pluck('id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedPermissions = [];
    }

    public function render()
    {
        return view('livewire.roles.edit-role');
    }
}
