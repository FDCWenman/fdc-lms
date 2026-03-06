<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">
        {{-- Logo --}}
        <div class="text-center">
            <img src="{{ asset('images/fdc.png') }}" alt="FDC Logo" class="mx-auto h-20 w-auto">
            <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                Request New Verification Link
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email address to receive a new verification link via Slack
            </p>
        </div>

        {{-- Form Card --}}
        <div class="rounded-lg bg-white px-6 py-8 shadow-md">
            <form wire:submit="requestVerification" class="space-y-6">
                {{-- Success Message --}}
                @if (session()->has('success'))
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- General Error --}}
                @error('form')
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $message }}
                                </p>
                            </div>
                        </div>
                    </div>
                @enderror

                {{-- Email Field --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>
                    <div class="mt-1">
                        <input
                            wire:model="email"
                            id="email"
                            type="email"
                            autocomplete="email"
                            required
                            class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm @error('email') border-red-300 @enderror"
                            placeholder="your.email@company.com"
                        >
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div>
                    <button
                        type="submit"
                        :disabled="$isSubmitting"
                        class="flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        @if ($isSubmitting)
                            <svg class="mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        @else
                            Send Verification Link
                        @endif
                    </button>
                </div>

                {{-- Back to Login Link --}}
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>

        {{-- Footer Help Text --}}
        <p class="text-center text-sm text-gray-500">
            The verification link will be sent to your Slack account.
        </p>
    </div>
</div>
