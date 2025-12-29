<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-primary">{{ __('auth.register_title') }}</h1>
        <p class="mt-2 text-sm text-content-secondary">{{ __('auth.register_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-content mb-2">{{ __('auth.name') }}</label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('name') ? 'border-error ring-1 ring-error' : '' }}">
            @error('name')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-content mb-2">{{ __('auth.email') }}</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
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
                   autocomplete="new-password"
                   class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all {{ $errors->has('password') ? 'border-error ring-1 ring-error' : '' }}">
            @error('password')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-content mb-2">{{ __('auth.confirm_password') }}</label>
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

        <!-- Register Button -->
        <div class="pt-2">
            <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                {{ __('auth.register_button') }}
            </button>
        </div>

        <!-- Sign in link -->
        <div class="text-center pt-2">
            <p class="text-sm text-content-secondary">
                {{ __('auth.already_registered') }}
                <a href="{{ route('login') }}" class="font-medium text-accent hover:text-accent-hover transition-colors">
                    {{ __('auth.sign_in') }}
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
