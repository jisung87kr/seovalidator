<nav x-data="{ open: false }" class="fixed w-full top-0 z-50 bg-white/80 backdrop-blur-md border-b border-border">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="/" class="text-lg font-bold text-primary">
                    SEO Validator
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden sm:flex items-center gap-1">
                    @if(Auth::check())
                        <a href="{{ route('dashboard') }}"
                           class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'text-accent bg-accent-light' : 'text-content-secondary hover:text-content hover:bg-surface-subtle' }}">
                            {{ __('ui.dashboard') }}
                        </a>
                        <a href="{{ route('analysis.history') }}"
                           class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('analysis.history') ? 'text-accent bg-accent-light' : 'text-content-secondary hover:text-content hover:bg-surface-subtle' }}">
                            {{ __('ui.history') }}
                        </a>
                    @else
                        <a href="{{ route('guest.analyses') }}"
                           class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('guest.analyses') ? 'text-accent bg-accent-light' : 'text-content-secondary hover:text-content hover:bg-surface-subtle' }}">
                            {{ __('ui.dashboard') }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Right Side -->
            <div class="hidden sm:flex items-center gap-3">
                <!-- Language Switcher -->
                <div class="flex items-center gap-1">
                    @if(app()->getLocale() === 'ko')
                        <a href="{{ url()->current() . '?locale=en' }}" class="px-2.5 py-1.5 text-sm text-content-secondary hover:text-content rounded-lg hover:bg-surface-subtle transition-colors">EN</a>
                        <span class="px-2.5 py-1.5 text-sm font-medium text-accent bg-accent-light rounded-lg">KO</span>
                    @else
                        <span class="px-2.5 py-1.5 text-sm font-medium text-accent bg-accent-light rounded-lg">EN</span>
                        <a href="{{ url()->current() . '?locale=ko' }}" class="px-2.5 py-1.5 text-sm text-content-secondary hover:text-content rounded-lg hover:bg-surface-subtle transition-colors">KO</a>
                    @endif
                </div>

                @if(Auth::check())
                    <!-- User Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-content-secondary hover:text-content rounded-lg hover:bg-surface-subtle transition-colors">
                                <div class="w-8 h-8 bg-accent-light text-accent rounded-full flex items-center justify-center text-sm font-semibold">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('user.profile')">
                                {{ __('ui.profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('ui.logout') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm text-content-secondary hover:text-content transition-colors">
                        {{ __('ui.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-medium text-white bg-primary hover:bg-primary-hover rounded-xl transition-colors">
                        {{ __('ui.register') }}
                    </a>
                @endif
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center gap-2 sm:hidden">
                <div class="flex items-center gap-1">
                    @if(app()->getLocale() === 'ko')
                        <a href="{{ url()->current() . '?locale=en' }}" class="px-2 py-1 text-xs text-content-secondary rounded">EN</a>
                        <span class="px-2 py-1 text-xs font-medium text-accent bg-accent-light rounded">KO</span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium text-accent bg-accent-light rounded">EN</span>
                        <a href="{{ url()->current() . '?locale=ko' }}" class="px-2 py-1 text-xs text-content-secondary rounded">KO</a>
                    @endif
                </div>
                <button @click="open = !open" class="p-2 rounded-lg text-content-secondary hover:text-content hover:bg-surface-subtle transition-colors">
                    <svg class="w-6 h-6" :class="{'hidden': open, 'block': !open}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    <svg class="w-6 h-6" :class="{'block': open, 'hidden': !open}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-cloak class="sm:hidden bg-white border-t border-border">
        <div class="px-4 py-3 space-y-1">
            @if(Auth::check())
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'text-accent bg-accent-light' : 'text-content hover:bg-surface-subtle' }} transition-colors">
                    {{ __('ui.dashboard') }}
                </a>
                <a href="{{ route('analysis.history') }}" class="block px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('analysis.history') ? 'text-accent bg-accent-light' : 'text-content hover:bg-surface-subtle' }} transition-colors">
                    {{ __('ui.history') }}
                </a>
            @else
                <a href="{{ route('guest.analyses') }}" class="block px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('guest.analyses') ? 'text-accent bg-accent-light' : 'text-content hover:bg-surface-subtle' }} transition-colors">
                    {{ __('ui.dashboard') }}
                </a>
            @endif
        </div>

        @if(Auth::check())
            <div class="px-4 py-3 border-t border-border">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-accent-light text-accent rounded-full flex items-center justify-center font-semibold">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium text-content">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-content-secondary">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <div class="space-y-1">
                    <a href="{{ route('user.profile') }}" class="block px-4 py-3 text-base text-content hover:bg-surface-subtle rounded-lg transition-colors">
                        {{ __('ui.profile') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-3 text-base text-content hover:bg-surface-subtle rounded-lg transition-colors">
                            {{ __('ui.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="px-4 py-3 border-t border-border space-y-2">
                <a href="{{ route('login') }}" class="block px-4 py-3 text-base text-content hover:bg-surface-subtle rounded-lg transition-colors text-center">
                    {{ __('ui.login') }}
                </a>
                <a href="{{ route('register') }}" class="block px-4 py-3 text-base font-medium text-white bg-primary hover:bg-primary-hover rounded-xl transition-colors text-center">
                    {{ __('ui.register') }}
                </a>
            </div>
        @endif
    </div>
</nav>
