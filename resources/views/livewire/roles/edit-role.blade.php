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
                            <label for="permission-{{ $permission['id'] }}" class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer has-[:checked]:bg-zinc-100 dark:has-[:checked]:bg-zinc-800 has-[:checked]:border-zinc-400 dark:has-[:checked]:border-zinc-500">
                                <input
                                    type="checkbox"
                                    wire:model="selectedPermissions"
                                    value="{{ $permission['id'] }}"
                                    id="permission-{{ $permission['id'] }}"
                                    class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:checked:bg-zinc-600"
                                >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $permission['name'] }}</div>
                                    @if($permission['description'])
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $permission['description'] }}</div>
                                    @endif
                                </div>
                            </label>
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
