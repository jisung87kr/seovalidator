<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.forgot_password_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.forgot_password_subtitle') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('auth.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Send Reset Link Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.send_reset_link') }}
            </x-primary-button>
        </div>

        <!-- Back to login link -->
        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                {{ __('auth.back_to_login') }}
            </a>
        </div>
    </form>
</x-guest-layout>
