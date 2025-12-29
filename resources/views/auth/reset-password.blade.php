<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-accent-light rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-primary">{{ __('auth.reset_password_title') }}</h1>
        <p class="mt-2 text-sm text-content-secondary">{{ __('auth.reset_password_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-content mb-2">{{ __('auth.email') }}</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email', $request->email) }}"
                   required
                   autofocus
                   autocomplete="username"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('email') ? 'border-error ring-1 ring-error' : '' }}">
            @error('email')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-content mb-2">{{ __('auth.new_password') }}</label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="new-password"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('password') ? 'border-error ring-1 ring-error' : '' }}">
            @error('password')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-content mb-2">{{ __('auth.confirm_new_password') }}</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('password_confirmation') ? 'border-error ring-1 ring-error' : '' }}">
            @error('password_confirmation')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Reset Password Button -->
        <div class="pt-2">
            <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                {{ __('auth.reset_password_button') }}
            </button>
        </div>

        <!-- Back to login link -->
        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="inline-flex items-center text-sm text-accent hover:text-accent-hover font-medium transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('auth.back_to_login') }}
            </a>
        </div>
    </form>
</x-guest-layout>
