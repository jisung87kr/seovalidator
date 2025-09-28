<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 flex items-center" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('ui.dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                <!-- Language Switcher -->
                <div class="relative">
                    <x-dropdown align="right" width="32">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none transition ease-in-out duration-150">
                                <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733C4.212 9.04 3.633 8.544 3.11 8.2a1 1 0 111.28-1.534c.649.43 1.406 1.034 2.11 1.87A16.829 16.829 0 007.858 6H7a1 1 0 110-2h1V3a1 1 0 011-1zm2 16a1 1 0 01-1-1v-2.065a1 1 0 01.27-.683l2.735-2.9.995 1.85a1 1 0 11-1.753.943L10.5 15.065V17a1 1 0 01-1 1z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ app()->getLocale() === 'ko' ? '한국어' : 'EN' }}</span>
                                <svg class="w-4 h-4 ms-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="url()->current() . '?locale=ko'">
                                <div class="flex items-center">
                                    <span class="me-2">🇰🇷</span>
                                    {{ __('ui.korean') }}
                                </div>
                            </x-dropdown-link>
                            <x-dropdown-link :href="url()->current() . '?locale=en'">
                                <div class="flex items-center">
                                    <span class="me-2">🇺🇸</span>
                                    {{ __('ui.english') }}
                                </div>
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('user.profile')">
                            {{ __('ui.profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('ui.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('ui.dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Language Switcher -->
                <div class="px-4 py-2">
                    <div class="text-sm font-medium text-gray-600 mb-2">{{ __('ui.language') }}</div>
                    <div class="flex space-x-2">
                        <a href="{{ url()->current() . '?locale=ko' }}"
                           class="inline-flex items-center px-3 py-1 text-xs bg-gray-100 rounded-full {{ app()->getLocale() === 'ko' ? 'bg-indigo-100 text-indigo-800' : 'text-gray-600' }}">
                            <span class="me-1">🇰🇷</span>
                            한국어
                        </a>
                        <a href="{{ url()->current() . '?locale=en' }}"
                           class="inline-flex items-center px-3 py-1 text-xs bg-gray-100 rounded-full {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-800' : 'text-gray-600' }}">
                            <span class="me-1">🇺🇸</span>
                            English
                        </a>
                    </div>
                </div>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('ui.profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('ui.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
