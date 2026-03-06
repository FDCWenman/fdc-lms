<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Permissions</flux:heading>
                <flux:subheading>View all available system permissions</flux:subheading>
            </div>
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.roles.index') }}">
                Back to Roles
            </flux:button>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <flux:input
            wire:model.live="search"
            placeholder="Search permissions..."
            icon="magnifying-glass"
        />
    </div>

    {{-- Permission Categories --}}
    @forelse($permissionsByCategory as $category => $permissions)
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">{{ $category ?: 'Uncategorized' }}</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($permissions as $permission)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <flux:icon.check-badge class="size-5 text-green-500 flex-shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <flux:text class="font-medium">{{ $permission['name'] }}</flux:text>
                            @if($permission['description'])
                                <flux:text size="sm" class="text-zinc-500 mt-0.5">
                                    {{ $permission['description'] }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @empty
        <flux:card class="text-center py-12">
            <flux:icon.magnifying-glass class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">No permissions found</flux:heading>
            <flux:text class="mt-2">Try adjusting your search query.</flux:text>
        </flux:card>
    @endforelse

    @if(!empty($permissionsByCategory))
        <flux:callout icon="information-circle" class="mt-6">
            <flux:callout.heading>About Permissions</flux:callout.heading>
            <flux:callout.text>
                These are system-defined permissions that can be assigned to roles. Users inherit permissions from their assigned roles.
            </flux:callout.text>
        </flux:callout>
    @endif
</div>
