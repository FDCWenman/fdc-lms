<div class="min-h-screen bg-gray-50 py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg">
            {{-- Header --}}
            <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Register New Employee</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Create a new user account with role assignment and Slack integration
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <div class="px-4 py-5 sm:p-6">
                <form wire:submit="register" class="space-y-6">
                    {{-- Success Message --}}
                    @if (session()->has('success'))
                        <div class="rounded-md bg-green-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- General Error --}}
                    @error('form')
                        <div class="rounded-md bg-red-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">{{ $message }}</p>
                                </div>
                            </div>
                        </div>
                    @enderror

                    {{-- Basic Information Section --}}
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900">Basic Information</h4>

                        {{-- Name --}}
                        <flux:field>
                            <flux:label for="name">Full Name *</flux:label>
                            <flux:input
                                wire:model="name"
                                id="name"
                                type="text"
                                placeholder="John Doe"
                                required
                            />
                            <flux:error name="name" />
                        </flux:field>

                        {{-- Email --}}
                        <flux:field>
                            <flux:label for="email">Email Address *</flux:label>
                            <flux:input
                                wire:model="email"
                                id="email"
                                type="email"
                                placeholder="john.doe@company.com"
                                required
                            />
                            <flux:error name="email" />
                        </flux:field>

                        {{-- Password --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label for="password">Password *</flux:label>
                                <flux:input
                                    wire:model="password"
                                    id="password"
                                    type="password"
                                    placeholder="••••••••"
                                    required
                                />
                                <flux:error name="password" />
                                <flux:description>Min 8 characters with mixed case, numbers, and symbols</flux:description>
                            </flux:field>

                            <flux:field>
                                <flux:label for="password_confirmation">Confirm Password *</flux:label>
                                <flux:input
                                    wire:model="password_confirmation"
                                    id="password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                    required
                                />
                                <flux:error name="password_confirmation" />
                            </flux:field>
                        </div>
                    </div>

                    {{-- Slack Integration Section --}}
                    <div class="space-y-4 border-t border-gray-200 pt-6">
                        <h4 class="text-md font-medium text-gray-900">Slack Integration</h4>

                        {{-- Slack ID with real-time validation --}}
                        <flux:field>
                            <flux:label for="slack_id">Slack ID *</flux:label>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <flux:input
                                        wire:model="slack_id"
                                        wire:blur="validateSlackId"
                                        id="slack_id"
                                        type="text"
                                        placeholder="U123456789"
                                        required
                                    />
                                </div>
                                @if ($isValidatingSlackId)
                                    <flux:button type="button" variant="ghost" disabled>
                                        <svg class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Validating...
                                    </flux:button>
                                @elseif ($slackIdValid)
                                    <div class="flex items-center text-green-600">
                                        <flux:icon.check class="size-5" />
                                        <span class="ml-1 text-sm">Valid</span>
                                    </div>
                                @endif
                            </div>
                            @if ($slackIdError)
                                <flux:error>{{ $slackIdError }}</flux:error>
                            @else
                                <flux:error name="slack_id" />
                            @endif
                            <flux:description>Format: U123456789 or W123456789. Will be validated in real-time.</flux:description>
                        </flux:field>
                    </div>

                    {{-- Role Assignment Section --}}
                    <div class="space-y-4 border-t border-gray-200 pt-6">
                        <h4 class="text-md font-medium text-gray-900">Role Assignment</h4>

                        <flux:field>
                            <flux:label>Select Roles *</flux:label>
                            <div class="space-y-2">
                                @foreach ($availableRoles as $role)
                                    <flux:checkbox
                                        wire:model="roles"
                                        value="{{ $role }}"
                                        :label="ucwords(str_replace('-', ' ', $role))"
                                    />
                                @endforeach
                            </div>
                            <flux:error name="roles" />
                            <flux:description>User can have multiple roles for different permissions</flux:description>
                        </flux:field>
                    </div>

                    {{-- Default Approvers Section --}}
                    <div class="space-y-4 border-t border-gray-200 pt-6">
                        <h4 class="text-md font-medium text-gray-900">Default Approvers</h4>
                        <p class="text-sm text-gray-500">
                            Assign default approvers for this employee's leave requests
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {{-- HR Approver --}}
                            <flux:field>
                                <flux:label for="hr_approver_id">HR Approver</flux:label>
                                <flux:select wire:model="hr_approver_id" id="hr_approver_id">
                                    <option value="">Select HR Approver</option>
                                    @foreach ($approvers as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="hr_approver_id" />
                            </flux:field>

                            {{-- Team Lead Approver --}}
                            <flux:field>
                                <flux:label for="tl_approver_id">Team Lead Approver</flux:label>
                                <flux:select wire:model="tl_approver_id" id="tl_approver_id">
                                    <option value="">Select Team Lead</option>
                                    @foreach ($approvers as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="tl_approver_id" />
                            </flux:field>

                            {{-- Project Manager Approver --}}
                            <flux:field>
                                <flux:label for="pm_approver_id">Project Manager Approver</flux:label>
                                <flux:select wire:model="pm_approver_id" id="pm_approver_id">
                                    <option value="">Select PM</option>
                                    @foreach ($approvers as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="pm_approver_id" />
                            </flux:field>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                        <a href="{{ route('portal') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Cancel
                        </a>

                        <flux:button
                            type="submit"
                            variant="primary"
                            :disabled="$isRegistering || $isValidatingSlackId"
                        >
                            @if ($isRegistering)
                                <svg class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Registering User...
                            @else
                                Register Employee
                            @endif
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
