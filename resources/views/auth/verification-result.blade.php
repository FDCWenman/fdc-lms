<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - FDCLeave</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            {{-- Logo --}}
            <div class="text-center">
                <img src="{{ asset('images/fdc.png') }}" alt="FDC Logo" class="mx-auto h-20 w-auto">
                <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                    Account Verification
                </h2>
            </div>

            {{-- Result Card --}}
            <div class="rounded-lg bg-white px-6 py-8 shadow-md">
                @if($success)
                    {{-- Success Message --}}
                    <div class="mb-4 flex items-center justify-center">
                        <svg class="h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <h3 class="mb-2 text-center text-xl font-semibold text-green-700">
                        Verification Successful!
                    </h3>

                    <p class="mb-6 text-center text-gray-600">
                        {{ $message }}
                    </p>

                    <div class="space-y-3">
                        <a href="{{ route('login') }}" class="flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            Go to Login
                        </a>
                    </div>
                @else
                    {{-- Error Message --}}
                    <div class="mb-4 flex items-center justify-center">
                        <svg class="h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <h3 class="mb-2 text-center text-xl font-semibold text-red-700">
                        Verification Failed
                    </h3>

                    <p class="mb-6 text-center text-gray-600">
                        {{ $message }}
                    </p>

                    <div class="space-y-3">
                        <a href="{{ route('auth.request-verification') }}" class="flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            Request New Verification Link
                        </a>

                        <a href="{{ route('login') }}" class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            Back to Login
                        </a>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <p class="text-center text-sm text-gray-500">
                Need help? Contact your HR administrator.
            </p>
        </div>
    </div>
</body>
</html>
