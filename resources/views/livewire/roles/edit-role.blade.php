<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg" class="bg-zinc-100 dark:bg-zinc-800">
                    {{ strtoupper(substr($role->name, 0, 2)) }}
                </flux:avatar>
                <div>
                    <flux:heading size="xl">{{ $role->name }}</flux:heading>
                    <flux:subheading>{{ $role->description ?? 'Configure permissions for this role' }}</flux:subheading>
                </div>
            </div>
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.roles.index') }}">
                Back to Roles
            </flux:button>
        </div>
    </div>

    @if (session()->has('message'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    <form wire:submit.prevent="updatePermissions">
        {{-- Action Buttons --}}
        <div class="mb-6 flex justify-end gap-2">
            <flux:button variant="ghost" size="sm" wire:click="selectAll">
                Select All
            </flux:button>
            <flux:button variant="ghost" size="sm" wire:click="deselectAll">
                Deselect All
            </flux:button>
        </div>

        {{-- Permission Categories --}}
        <div class="space-y-6">
            @foreach($permissionsByCategory as $category => $permissions)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">{{ $category ?: 'Uncategorized' }}</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($permissions as $permission)
                            <flux:checkbox
                                wire:model="selectedPermissions"
                                value="{{ $permission['id'] }}"
                                id="permission-{{ $permission['id'] }}"
                            >
                                <flux:checkbox.label>{{ $permission['name'] }}</flux:checkbox.label>
                                @if($permission['description'])
                                    <flux:checkbox.description>{{ $permission['description'] }}</flux:checkbox.description>
                                @endif
                            </flux:checkbox>
                        @endforeach
                    </div>
                </flux:card>
            @endforeach
        </div>

        @error('selectedPermissions')
            <flux:callout variant="danger" icon="exclamation-circle" class="mt-4">
                {{ $message }}
            </flux:callout>
        @enderror

        @error('selectedPermissions.*')
            <flux:callout variant="danger" icon="exclamation-circle" class="mt-4">
                {{ $message }}
            </flux:callout>
        @enderror

        <div class="flex justify-end mt-6">
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Update Permissions</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
