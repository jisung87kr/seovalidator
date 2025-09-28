<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.register_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.register_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('auth.name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('auth.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth.confirm_password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Register Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.register_button') }}
            </x-primary-button>
        </div>

        <!-- Sign in link -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                {{ __('auth.already_registered') }}
                <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                    {{ __('auth.sign_in') }}
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
