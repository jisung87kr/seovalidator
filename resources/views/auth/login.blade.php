<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.login_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.login_subtitle') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('auth.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ml-2 text-sm text-gray-600">{{ __('auth.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-indigo-600 hover:text-indigo-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('auth.forgot_password') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.login_button') }}
            </x-primary-button>
        </div>

        <!-- Sign up link -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                {{ __('auth.no_account') }}
                <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                    {{ __('auth.create_account') }}
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
