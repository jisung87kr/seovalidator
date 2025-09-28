<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.verify_email_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.verify_email_subtitle') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        {{ __('auth.verification_sent') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Resend Verification Button -->
    <div class="mt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                {{ __('auth.resend_verification') }}
            </x-primary-button>
        </form>
    </div>

    <!-- Logout link -->
    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('auth.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
