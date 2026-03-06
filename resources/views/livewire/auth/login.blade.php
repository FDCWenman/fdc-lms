<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Logo --}}
        <div class="text-center">
            <img src="{{ asset('images/fdc.png') }}" alt="FDCLeave Logo" class="mx-auto h-16 w-auto">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Sign in to FDCLeave
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Leave Management System
            </p>
        </div>

        {{-- Login Form --}}
        <flux:card class="mt-8">
            <form wire:submit="login" class="space-y-6">
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
                </flux:field>

                {{-- Password Field --}}
                <flux:field>
                    <flux:label for="password">Password</flux:label>
                    <flux:input 
                        wire:model="password" 
                        id="password" 
                        type="password" 
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    />
                    <flux:error name="password" />
                </flux:field>

                {{-- Submit Button --}}
                <flux:button type="submit" variant="primary" class="w-full">
                    <span wire:loading.remove>Sign in</span>
                    <span wire:loading>Signing in...</span>
                </flux:button>
            </form>
        </flux:card>

        {{-- Additional Links --}}
        <div class="text-center text-sm text-gray-600 mb-2">
            <p>For assistance, please contact your HR administrator.</p>
        </div>

         <div class="text-center text-sm text-gray-600 mb-2">
            <p>Don't have an account? <flux:link :href="route('register')" wire:navigate>Register here</flux:link></p>
        </div>

        {{-- Forgot password --}}
        <div class="text-center text-sm text-gray-600">
            @if (Route::has('password.request'))
                <flux:link :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>
    </div>
</div>
