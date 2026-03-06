<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
        <p class="mt-1 text-sm text-gray-600">View all role and permission management activities</p>
    </div>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Action Type Filter --}}
            <div>
                <label for="actionFilter" class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                <select wire:model.live="actionFilter" id="actionFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Actions</option>
                    @foreach($actionTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- User Filter --}}
            <div>
                <label for="userFilter" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select wire:model.live="userFilter" id="userFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From Filter --}}
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input wire:model.live="dateFrom" type="date" id="dateFrom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Date To Filter --}}
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input wire:model.live="dateTo" type="date" id="dateTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <div class="mt-4">
            <button wire:click="clearFilters" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Clear Filters
            </button>
        </div>
    </div>

    {{-- Activity Log Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date/Time
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Subject
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Details
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($activities as $activity)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $activity->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $activity->causer?->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                @if($activity->description === 'created') bg-green-100 text-green-800
                                @elseif($activity->description === 'updated') bg-blue-100 text-blue-800
                                @elseif($activity->description === 'deleted') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($activity->description) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($activity->subject)
                                {{ class_basename($activity->subject_type) }}: {{ $activity->subject->name ?? $activity->subject_id }}
                            @else
                                Deleted
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if($activity->properties->isNotEmpty())
                                @if($activity->properties->has('old'))
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 hover:text-indigo-900">View Changes</summary>
                                        <div class="mt-2 p-2 bg-gray-50 rounded text-xs">
                                            @if($activity->properties->has('old'))
                                                <div class="mb-2">
                                                    <strong>Before:</strong>
                                                    <pre class="mt-1">{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if($activity->properties->has('attributes'))
                                                <div>
                                                    <strong>After:</strong>
                                                    <pre class="mt-1">{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    {{ $activity->properties->toJson() }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            No audit logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $activities->links() }}
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.roles.index') }}" class="text-indigo-600 hover:text-indigo-900">
            ← Back to Roles
        </a>
    </div>
</div>
