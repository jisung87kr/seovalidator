<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-primary">{{ __('auth.login_title') }}</h1>
        <p class="mt-2 text-sm text-content-secondary">{{ __('auth.login_subtitle') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-6 p-4 bg-success-light border border-success/20 text-success-dark rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-content mb-2">{{ __('auth.email') }}</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
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

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me"
                       type="checkbox"
                       name="remember"
                       class="h-4 w-4 text-accent focus:ring-accent border-border rounded">
                <span class="ml-2 text-sm text-content-secondary">{{ __('auth.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-accent hover:text-accent-hover font-medium transition-colors">
                    {{ __('auth.forgot_password') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div class="pt-2">
            <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                {{ __('auth.login_button') }}
            </button>
        </div>

        <!-- Sign up link -->
        <div class="text-center pt-2">
            <p class="text-sm text-content-secondary">
                {{ __('auth.no_account') }}
                <a href="{{ route('register') }}" class="font-medium text-accent hover:text-accent-hover transition-colors">
                    {{ __('auth.create_account') }}
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
