<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;

class ViewPermissions extends Component
{
    public $search = '';

    public $permissionsByCategory = [];

    public function mount()
    {
        $this->loadPermissions();
    }

    public function updatedSearch()
    {
        $this->loadPermissions();
    }

    protected function loadPermissions()
    {
        $query = Permission::query();

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        $permissions = $query->get();

        $this->permissionsByCategory = $permissions->groupBy('category')->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'category' => $permission->category,
                ];
            });
        })->toArray();
    }

    public function render()
    {
        return view('livewire.roles.view-permissions');
    }
}
