<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">System Permissions</h2>
                    <a href="{{ route('admin.roles.index') }}" class="text-blue-600 hover:text-blue-800">
                        Back to Roles
                    </a>
                </div>

                <div class="mb-6">
                    <input 
                        type="text" 
                        wire:model.live="search" 
                        placeholder="Search permissions..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                @forelse($permissionsByCategory as $category => $permissions)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                            {{ $category ?: 'Uncategorized' }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($permissions as $permission)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $permission['name'] }}
                                            </p>
                                            @if($permission['description'])
                                                <p class="mt-1 text-xs text-gray-600">
                                                    {{ $permission['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No permissions found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search query.</p>
                    </div>
                @endforelse

                @if(!empty($permissionsByCategory))
                    <div class="mt-8 border-t pt-6">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">About Permissions</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>These are system-defined permissions that can be assigned to roles. Users inherit permissions from their assigned roles.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
