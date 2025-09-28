<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('auth.confirm_password_title') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('auth.confirm_password_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('auth.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Button -->
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.confirm_button') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
