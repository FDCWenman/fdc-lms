<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </flux:avatar>
                <div>
                    <flux:heading size="xl">{{ $user->name }}</flux:heading>
                    <flux:subheading>{{ $user->email }}</flux:subheading>
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Roles Selection --}}
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Available Roles</flux:heading>

                <form wire:submit.prevent="updateRoles">
                    <div class="space-y-3">
                        @foreach($roles as $role)
                            <div class="flex items-start p-4 rounded-xl border {{ in_array($role->id, $selectedRoles) ? 'bg-zinc-50 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <flux:checkbox
                                    wire:model="selectedRoles"
                                    value="{{ $role->id }}"
                                    id="role-{{ $role->id }}"
                                />
                                <div class="ml-3 flex-1">
                                    <label for="role-{{ $role->id }}" class="cursor-pointer">
                                        <div class="flex items-center gap-2">
                                            <flux:heading size="sm">{{ $role->name }}</flux:heading>
                                            @if($role->is_protected)
                                                <flux:badge color="amber" size="sm" icon="shield-check">System</flux:badge>
                                            @endif
                                        </div>
                                        @if($role->description)
                                            <flux:text class="mt-1">{{ $role->description }}</flux:text>
                                        @endif
                                        <flux:text size="sm" class="mt-2 text-zinc-500">
                                            {{ $role->permissions->count() }} permissions
                                        </flux:text>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('selectedRoles')
                        <flux:callout variant="danger" icon="exclamation-circle" class="mt-4">
                            {{ $message }}
                        </flux:callout>
                    @enderror

                    <div class="flex justify-end mt-6">
                        <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>Update Roles</span>
                            <span wire:loading>Saving...</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        {{-- Current Permissions Sidebar --}}
        <div>
            <flux:card>
                <flux:heading size="lg" class="mb-4">Current Permissions</flux:heading>
                
                @if($user->getAllPermissions()->count() > 0)
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($user->getAllPermissions() as $permission)
                            <div class="flex items-center gap-2">
                                <flux:icon.check-circle class="size-4 text-green-500" />
                                <flux:text size="sm">{{ $permission->name }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <flux:icon.shield-exclamation class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-2">No permissions assigned</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>
    </div>
</div>
