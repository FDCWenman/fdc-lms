<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header Section --}}
    <div class="mb-8">
        <flux:heading size="xl">Employees</flux:heading>
        <p class="mb-1 text-zinc-500 dark:text-zinc-400">View and manage employee information</p>
    </div>

    {{-- Search and Filter Section --}}
    <flux:card class="mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Search Input --}}
            <div class="flex-1">
                <flux:input 
                    wire:model.live="search" 
                    placeholder="Search by name or email..." 
                    icon="magnifying-glass"
                    clearable
                />
            </div>

            {{-- Status Filter --}}
            <div class="sm:w-48">
                <flux:select wire:model.live="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="0">Pending Verification</option>
                    <option value="1">Active</option>
                    <option value="2">Deactivated</option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Loading State --}}
    <div wire:loading.delay class="mb-4">
        <flux:callout variant="info" icon="arrow-path">
            Loading employees...
        </flux:callout>
    </div>

    {{-- Employee List --}}
    @if ($this->employees->count() > 0)
        <flux:card>
            <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Employee
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Roles
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->employees as $employee)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            {{-- Employee Name with Avatar --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm">
                                        {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                    </flux:avatar>
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">
                                            {{ $employee->first_name }} 
                                            @if($employee->middle_name)
                                                {{ $employee->middle_name }} 
                                            @endif
                                            {{ $employee->last_name }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $employee->email }}</span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($employee->status === 0)
                                    <flux:badge color="yellow" size="sm">Pending Verification</flux:badge>
                                @elseif($employee->status === 1)
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @elseif($employee->status === 2)
                                    <flux:badge color="red" size="sm">Deactivated</flux:badge>
                                @endif
                            </td>

                            {{-- Roles --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($employee->roles as $role)
                                        <flux:badge color="zinc" size="sm">{{ $role->name }}</flux:badge>
                                    @empty
                                        <span class="text-zinc-400 dark:text-zinc-500 text-sm italic">No roles</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($this->employees->hasPages())
                <div class="mt-4">
                    {{ $this->employees->links() }}
                </div>
            @endif
        </flux:card>
    @else
        {{-- Empty State --}}
        <flux:card>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <flux:heading size="lg" class="mt-4">No employees found</flux:heading>
                <p class="mt-2 text-zinc-500 dark:text-zinc-400">
                    @if($search || $statusFilter !== null)
                        Try adjusting your search or filter criteria.
                    @else
                        There are no employees in the system yet.
                    @endif
                </p>
            </div>
        </flux:card>
    @endif
</div>
