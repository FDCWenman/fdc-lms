<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Logo --}}
        <div class="text-center">
            <img src="{{ asset('images/fdc.png') }}" alt="FDCLeave Logo" class="mx-auto h-16 w-auto">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Forgot Your Password?
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email and we'll send a reset link via Slack
            </p>
        </div>

        {{-- Forgot Password Form --}}
        <flux:card class="mt-8">
            {{-- Success Message --}}
            @if (session()->has('success'))
                <div class="rounded-md bg-green-50 p-4 mb-6">
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

            <form wire:submit="sendResetLink" class="space-y-6">
                {{-- Email Field --}}
                <flux:field>
                    <flux:label for="email">Email Address</flux:label>
                    <flux:input 
                        wire:model="email" 
                        id="email" 
                        type="email" 
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    />
                    <flux:error name="email" />
                    <flux:description>
                        We'll send a password reset link to your Slack DM
                    </flux:description>
                </flux:field>

                {{-- Submit Button --}}
                <flux:button 
                    type="submit" 
                    variant="primary" 
                    class="w-full"
                    :disabled="$isSending"
                >
                    @if ($isSending)
                        <svg class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Sending Reset Link...</span>
                    @else
                        Send Reset Link
                    @endif
                </flux:button>
            </form>
        </flux:card>

        {{-- Back to Login Link --}}
        <div class="text-center text-sm text-gray-600">
            <flux:link :href="route('login')" wire:navigate>
                Back to login
            </flux:link>
        </div>
    </div>
</div>
