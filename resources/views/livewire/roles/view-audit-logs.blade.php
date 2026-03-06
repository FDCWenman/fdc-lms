<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Audit Logs</flux:heading>
                <flux:subheading>View all role and permission management activities</flux:subheading>
            </div>
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.roles.index') }}">
                Back to Roles
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:select wire:model.live="actionFilter" label="Action Type">
                <option value="">All Actions</option>
                @foreach($actionTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="userFilter" label="User">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live="dateFrom"
                type="date"
                label="From Date"
            />

            <flux:input
                wire:model.live="dateTo"
                type="date"
                label="To Date"
            />
        </div>

        <div class="mt-4">
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                Clear Filters
            </flux:button>
        </div>
    </flux:card>

    {{-- Activity Log Table --}}
    <flux:card class="overflow-hidden !p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Date/Time
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Action
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Subject
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            Details
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($activities as $activity)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:text size="sm">{{ $activity->created_at->format('Y-m-d H:i:s') }}</flux:text>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs">
                                        {{ $activity->causer ? strtoupper(substr($activity->causer->name, 0, 2)) : 'SY' }}
                                    </flux:avatar>
                                    <flux:text size="sm">{{ $activity->causer?->name ?? 'System' }}</flux:text>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $color = match($activity->description) {
                                        'created' => 'green',
                                        'updated' => 'blue',
                                        'deleted' => 'red',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge color="{{ $color }}" size="sm">
                                    {{ ucfirst($activity->description) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4">
                                <flux:text size="sm">
                                    @if($activity->subject)
                                        {{ class_basename($activity->subject_type) }}: {{ $activity->subject->name ?? $activity->subject_id }}
                                    @else
                                        <span class="text-zinc-400">Deleted</span>
                                    @endif
                                </flux:text>
                            </td>
                            <td class="px-6 py-4">
                                @if($activity->properties->isNotEmpty())
                                    @if($activity->properties->has('old'))
                                        <details class="cursor-pointer">
                                            <summary class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View Changes</summary>
                                            <div class="mt-2 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-xs space-y-2">
                                                @if($activity->properties->has('old'))
                                                    <div>
                                                        <flux:text size="xs" class="font-medium">Before:</flux:text>
                                                        <pre class="mt-1 text-zinc-600 dark:text-zinc-400 overflow-x-auto">{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                                @if($activity->properties->has('attributes'))
                                                    <div>
                                                        <flux:text size="xs" class="font-medium">After:</flux:text>
                                                        <pre class="mt-1 text-zinc-600 dark:text-zinc-400 overflow-x-auto">{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <flux:text size="sm" class="text-zinc-500 truncate max-w-xs">
                                            {{ Str::limit($activity->properties->toJson(), 50) }}
                                        </flux:text>
                                    @endif
                                @else
                                    <flux:text size="sm" class="text-zinc-400">—</flux:text>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400" />
                                <flux:heading size="sm" class="mt-4">No audit logs found</flux:heading>
                                <flux:text class="mt-1">Try adjusting your filters.</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $activities->links() }}
            </div>
        @endif
    </flux:card>
</div>
