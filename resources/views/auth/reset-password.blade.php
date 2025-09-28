<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.reset_password_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.reset_password_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('auth.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.new_password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth.confirm_new_password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Reset Password Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.reset_password_button') }}
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
