<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Account Inactive') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-6 text-center">
                <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Account Inactive') }}</h2>
            </div>

            <div class="mb-6 text-center text-gray-600">
                <p class="mb-4">
                    {{ __('Your account has been deactivated due to inactivity.') }}
                </p>
                @if(isset($reason) && $reason)
                    <p class="text-sm text-gray-500 mb-4">
                        {{ __('Reason:') }} {{ $reason }}
                    </p>
                @endif
                @if(isset($deactivatedAt) && $deactivatedAt)
                    <p class="text-sm text-gray-500 mb-4">
                        {{ __('Deactivated at:') }} {{ $deactivatedAt->format('Y-m-d H:i:s') }}
                    </p>
                @endif
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm font-medium text-blue-900">
                        {{ __('Please contact your administrator to reactivate your account.') }}
                    </p>
                    @if(config('password-security.support.email'))
                        <p class="mt-2 text-sm text-blue-700">
                            <a href="mailto:{{ config('password-security.support.email') }}" class="underline hover:text-blue-900">
                                {{ config('password-security.support.email') }}
                            </a>
                        </p>
                    @endif
                    @if(config('password-security.support.phone'))
                        <p class="mt-1 text-sm text-blue-700">
                            {{ __('Phone:') }} {{ config('password-security.support.phone') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150">
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-gray-500">
            <p>{{ __('Accounts are automatically deactivated after a period of inactivity for security purposes.') }}</p>
        </div>
    </div>
</body>
</html>

