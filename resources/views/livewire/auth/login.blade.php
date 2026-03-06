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

                {{-- Remember Me --}}
                <div class="flex items-center">
                    <flux:checkbox wire:model="remember" id="remember" />
                    <flux:label for="remember" class="ml-2">Remember me</flux:label>
                </div>

                {{-- Submit Button --}}
                <flux:button type="submit" variant="primary" class="w-full">
                    <span wire:loading.remove>Sign in</span>
                    <span wire:loading>Signing in...</span>
                </flux:button>
            </form>
        </flux:card>

        {{-- Additional Links --}}
        <div class="text-center text-sm text-gray-600">
            <p>For assistance, please contact your HR administrator.</p>
        </div>
    </div>
</div>
