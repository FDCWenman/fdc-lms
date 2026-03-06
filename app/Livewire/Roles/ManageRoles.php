<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ManageRoles extends Component
{
    use WithPagination;

    public $showCreateModal = false;

    public $showEditModal = false;

    public $showDeleteModal = false;

    public $name = '';

    public $description = '';

    public $roleId = null;

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\s\-_]+$/', 'unique:roles,name,'.$this->roleId],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected $messages = [
        'name.required' => 'Role name is required.',
        'name.max' => 'Role name cannot exceed 50 characters.',
        'name.regex' => 'Role name can only contain letters, numbers, spaces, hyphens, and underscores.',
        'name.unique' => 'A role with this name already exists.',
        'description.max' => 'Description cannot exceed 500 characters.',
    ];

    public function openCreateModal()
    {
        $this->resetFields();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetFields();
    }

    public function createRole($data = null)
    {
        // Handle both direct call and form submission
        if ($data) {
            $this->name = $data['name'] ?? '';
            $this->description = $data['description'] ?? null;
        }

        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'guard_name' => 'web',
            'description' => $this->description,
            'is_protected' => false,
        ]);

        // Log activity using spatie/laravel-activitylog
        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->log('created');

        session()->flash('message', 'Role created successfully.');

        $this->closeCreateModal();
        $this->resetFields();
    }

    public function openEditModal($roleId)
    {
        $role = Role::findOrFail($roleId);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->description = $role->description;

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetFields();
    }

    public function updateRole()
    {
        $this->validate();

        $role = Role::findOrFail($this->roleId);

        $oldValues = $role->only(['name', 'description']);

        $role->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        // Log activity
        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $oldValues,
                'attributes' => $role->only(['name', 'description']),
            ])
            ->log('updated');

        session()->flash('message', 'Role updated successfully.');

        $this->closeEditModal();
        $this->resetFields();
    }

    public function confirmDelete($roleId)
    {
        $this->roleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->roleId = null;
    }

    public function deleteRole($roleId = null)
    {
        $id = $roleId ?? $this->roleId;
        $role = Role::findOrFail($id);

        // Check if role is protected
        if ($role->is_protected) {
            $this->addError('role', 'Cannot delete protected system role.');

            return;
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            $this->addError('role', 'Cannot delete role with assigned users. Please reassign users first.');

            return;
        }

        // Log activity before deletion
        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $role->toArray(),
            ])
            ->log('deleted');

        $role->delete();

        session()->flash('message', 'Role deleted successfully.');

        $this->closeDeleteModal();
    }

    private function resetFields()
    {
        $this->name = '';
        $this->description = '';
        $this->roleId = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $roles = Role::withCount('users')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.roles.manage-roles', [
            'roles' => $roles,
        ]);
    }
}
