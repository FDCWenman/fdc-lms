<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Assign Roles: {{ $user->name }}</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ $user->email }}</p>
                    </div>
                    <a href="{{ route('admin.roles.index') }}" class="text-blue-600 hover:text-blue-800">
                        Back to Roles
                    </a>
                </div>

                @if (session()->has('message'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('message') }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="updateRoles">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Roles</h3>

                        <div class="space-y-4">
                            @foreach($roles as $role)
                                <div class="flex items-start border rounded-lg p-4 {{ in_array($role->id, $selectedRoles) ? 'bg-blue-50 border-blue-300' : 'border-gray-200' }}">
                                    <div class="flex items-center h-5">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedRoles" 
                                            value="{{ $role->id }}"
                                            id="role-{{ $role->id }}"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                        >
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <label for="role-{{ $role->id }}" class="font-medium text-gray-900 cursor-pointer flex items-center">
                                            {{ $role->name }}
                                            @if($role->is_protected)
                                                <span class="ml-2 inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                    Protected
                                                </span>
                                            @endif
                                        </label>
                                        @if($role->description)
                                            <p class="text-sm text-gray-600 mt-1">{{ $role->description }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-2">
                                            Permissions: {{ $role->permissions->pluck('name')->implode(', ') ?: 'None' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @error('selectedRoles')
                        <div class="text-red-500 text-sm mb-4">{{ $message }}</div>
                    @enderror

                    @error('selectedRoles.*')
                        <div class="text-red-500 text-sm mb-4">{{ $message }}</div>
                    @enderror

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Update Roles
                        </button>
                    </div>
                </form>

                <div class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Permissions</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        @if($user->permissions->count() > 0)
                            <div class="text-sm text-gray-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($user->getAllPermissions() as $permission)
                                        <li>{{ $permission->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No permissions assigned yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
