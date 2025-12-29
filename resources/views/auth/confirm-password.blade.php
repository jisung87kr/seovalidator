<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-warning-light rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-primary">{{ __('auth.confirm_password_title') }}</h1>
        <p class="mt-2 text-sm text-content-secondary">{{ __('auth.confirm_password_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-content mb-2">{{ __('auth.password') }}</label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="current-password"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('password') ? 'border-error ring-1 ring-error' : '' }}">
            @error('password')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Button -->
        <div class="pt-2">
            <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                {{ __('auth.confirm_button') }}
            </button>
        </div>
    </form>
</x-guest-layout>
