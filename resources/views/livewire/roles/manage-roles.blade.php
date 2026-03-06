<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header Section --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Roles</flux:heading>
                <p class="mb-1 text-zinc-500 dark:text-zinc-400">Manage system roles and their permissions</p>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="lock-closed" href="{{ route('admin.roles.permissions') }}">
                    Permissions
                </flux:button>
                <flux:button variant="ghost" icon="document-text" href="{{ route('admin.roles.audit-logs') }}">
                    Audit Logs
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    New Role
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if (session()->has('message'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if ($errors->has('role'))
        <flux:callout variant="danger" icon="exclamation-circle" class="mb-6">
            {{ $errors->first('role') }}
        </flux:callout>
    @endif

    {{-- Roles Grid --}}
    @if ($roles->count() > 0)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($roles as $role)
                <flux:card class="group">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg" class="truncate">{{ $role->name }}</flux:heading>
                            @if ($role->is_protected)
                                <svg class="size-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            @endif
                        </div>
                        <flux:text class="line-clamp-2 mt-1">
                            {{ $role->description ?? 'No description provided' }}
                        </flux:text>
                    </div>

                    <flux:separator class="my-4" />

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <flux:text size="sm">{{ $role->users_count }} users</flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <flux:text size="sm">{{ $role->permissions_count ?? 0 }} permissions</flux:text>
                            </div>
                        </div>

                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                            <flux:menu>
                                <flux:menu.item icon="lock-closed" href="{{ route('admin.roles.edit', $role->id) }}">
                                    Permissions
                                </flux:menu.item>
                                <flux:menu.item icon="pencil" wire:click="openEditModal({{ $role->id }})">
                                    Edit
                                </flux:menu.item>
                                @if (!$role->is_protected)
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $role->id }})">
                                        Delete
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </flux:card>
            @endforeach
        </div>

        @if($roles->hasPages())
            <div class="mt-6">
                {{ $roles->links() }}
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <flux:card class="text-center py-12">
            <flux:icon.user-group class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">No roles found</flux:heading>
            <flux:text class="mt-2">Get started by creating your first role.</flux:text>
            <div class="mt-6">
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Create Role
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Create Role Modal --}}
    <flux:modal wire:model="showCreateModal" class="max-w-md">
        <form wire:submit.prevent="createRole" class="space-y-6">
            <div>
                <flux:heading size="lg">Create New Role</flux:heading>
                <flux:subheading>Add a new role to your system</flux:subheading>
            </div>

            <flux:input
                wire:model="name"
                label="Role Name"
                placeholder="e.g., Content Manager"
                required
            />

            <flux:textarea
                wire:model="description"
                label="Description"
                placeholder="Describe what this role can do..."
                rows="3"
            />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeCreateModal">
                    Cancel
                </flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Create Role</span>
                    <span wire:loading>Creating...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Role Modal --}}
    <flux:modal wire:model="showEditModal" class="max-w-md">
        <form wire:submit.prevent="updateRole" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Role</flux:heading>
                <flux:subheading>Update role details</flux:subheading>
            </div>

            <flux:input
                wire:model="name"
                label="Role Name"
                required
            />

            <flux:textarea
                wire:model="description"
                label="Description"
                rows="3"
            />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeEditModal">
                    Cancel
                </flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Update Role</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="space-y-6">
            <div class="flex flex-col items-center text-center">
                <div class="flex items-center justify-center size-12 rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
                </div>
                <flux:heading size="lg" class="mt-4">Delete Role?</flux:heading>
                <flux:text class="mt-2">
                    This action cannot be undone. Users assigned to this role will lose their permissions.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:button variant="ghost" class="flex-1" wire:click="closeDeleteModal">
                    Cancel
                </flux:button>
                <flux:button variant="danger" class="flex-1" wire:click="deleteRole" wire:loading.attr="disabled">
                    <span wire:loading.remove>Delete</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
