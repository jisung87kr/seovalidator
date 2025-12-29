<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-accent-light rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-primary">{{ __('auth.forgot_password_title') }}</h1>
        <p class="mt-2 text-sm text-content-secondary">{{ __('auth.forgot_password_subtitle') }}</p>
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

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('email') ? 'border-error ring-1 ring-error' : '' }}">
            @error('email')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Send Reset Link Button -->
        <div class="pt-2">
            <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                {{ __('auth.send_reset_link') }}
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
