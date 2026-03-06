<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Logo --}}
        <div class="text-center">
            <img src="{{ asset('images/fdc.png') }}" alt="FDCLeave Logo" class="mx-auto h-16 w-auto">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Reset Your Password
            </h2>
            @if ($tokenValid && $user)
                <p class="mt-2 text-sm text-gray-600">
                    Enter a new password for {{ $user->email }}
                </p>
            @endif
        </div>

        <flux:card class="mt-8">
            @if (!$tokenValid)
                {{-- Invalid Token --}}
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Invalid or Expired Token</h3>
                            <p class="mt-2 text-sm text-red-700">
                                This password reset link is invalid or has expired. Please request a new one.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <flux:link :href="route('password.request')" wire:navigate>
                        Request new reset link
                    </flux:link>
                </div>
            @else
                {{-- Reset Password Form --}}
                <form wire:submit="resetPassword" class="space-y-6">
                    {{-- Password Field --}}
                    <flux:field>
                        <flux:label for="password">New Password</flux:label>
                        <flux:input 
                            wire:model="password" 
                            id="password" 
                            type="password" 
                            placeholder="••••••••"
                            autocomplete="new-password"
                            required
                        />
                        <flux:error name="password" />
                        <flux:description>Min 8 characters</flux:description>
                    </flux:field>

                    {{-- Password Confirmation Field --}}
                    <flux:field>
                        <flux:label for="password_confirmation">Confirm New Password</flux:label>
                        <flux:input 
                            wire:model="password_confirmation" 
                            id="password_confirmation" 
                            type="password" 
                            placeholder="••••••••"
                            autocomplete="new-password"
                            required
                        />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    {{-- Submit Button --}}
                    <flux:button 
                        type="submit" 
                        variant="primary" 
                        class="w-full"
                        :disabled="$isResetting"
                    >
                        @if ($isResetting)
                            <svg class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Resetting Password...</span>
                        @else
                            Reset Password
                        @endif
                    </flux:button>
                </form>
            @endif
        </flux:card>

        {{-- Back to Login Link --}}
        <div class="text-center text-sm text-gray-600">
            <flux:link :href="route('login')" wire:navigate>
                Back to login
            </flux:link>
        </div>
    </div>
</div>
