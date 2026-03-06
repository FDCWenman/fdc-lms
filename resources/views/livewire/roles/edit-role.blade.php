<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Edit Role: {{ $role->name }}</h2>
                        @if($role->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $role->description }}</p>
                        @endif
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

                <form wire:submit.prevent="updatePermissions">
                    <div class="mb-6 flex justify-end space-x-2">
                        <button type="button" wire:click="selectAll" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            Select All
                        </button>
                        <button type="button" wire:click="deselectAll" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            Deselect All
                        </button>
                    </div>

                    @foreach($permissionsByCategory as $category => $permissions)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                                {{ $category ?: 'Uncategorized' }}
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($permissions as $permission)
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                wire:model="selectedPermissions" 
                                                value="{{ $permission['id'] }}"
                                                id="permission-{{ $permission['id'] }}"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="permission-{{ $permission['id'] }}" class="font-medium text-gray-900 cursor-pointer">
                                                {{ $permission['name'] }}
                                            </label>
                                            @if($permission['description'])
                                                <p class="text-xs text-gray-500">{{ $permission['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @error('selectedPermissions')
                        <div class="text-red-500 text-sm mb-4">{{ $message }}</div>
                    @enderror

                    @error('selectedPermissions.*')
                        <div class="text-red-500 text-sm mb-4">{{ $message }}</div>
                    @enderror

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Update Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
