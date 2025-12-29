<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-6R50J0Y4TK"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-6R50J0Y4TK');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('landing.page_title') }}</title>
    <meta name="description" content="{{ __('landing.page_description') }}">
    <meta name="keywords" content="{{ __('landing.page_keywords') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ __('landing.og_title') }}">
    <meta property="og:description" content="{{ __('landing.og_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('landing.twitter_title') }}">
    <meta name="twitter:description" content="{{ __('landing.twitter_description') }}">

    <!-- Hreflang for SEO -->
    <link rel="alternate" hreflang="ko" href="{{ url('/') }}">
    <link rel="alternate" hreflang="en" href="{{ url('/en') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            word-break: keep-all;
        }
        @media (max-width: 768px) {
            input[type="url"], input[type="text"], input[type="email"] {
                font-size: 16px;
            }
        }
    </style>
</head>
<body class="antialiased bg-surface-muted text-content">
    <!-- Navigation -->
    <nav class="fixed w-full top-0 z-50 bg-white/80 backdrop-blur-md border-b border-border">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-lg font-bold text-primary">SEO Validator</a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-2">
                    <!-- Language Switcher -->
                    <div class="flex items-center gap-1 mr-4">
                        @if(app()->getLocale() === 'ko')
                            <a href="{{ url('/en') }}" class="px-2.5 py-1.5 text-sm text-content-secondary hover:text-content rounded-lg hover:bg-surface-subtle transition-colors">EN</a>
                            <span class="px-2.5 py-1.5 text-sm font-medium text-accent bg-accent-light rounded-lg">KO</span>
                        @else
                            <span class="px-2.5 py-1.5 text-sm font-medium text-accent bg-accent-light rounded-lg">EN</span>
                            <a href="{{ url('/') }}" class="px-2.5 py-1.5 text-sm text-content-secondary hover:text-content rounded-lg hover:bg-surface-subtle transition-colors">KO</a>
                        @endif
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-content-secondary hover:text-content transition-colors">
                            {{ __('landing.dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('guest.analyses') }}" class="px-4 py-2 text-sm text-content-secondary hover:text-content transition-colors">
                            {{ __('guest.my_analyses') }}
                        </a>
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm text-content-secondary hover:text-content transition-colors">
                            {{ __('landing.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-medium text-white bg-primary hover:bg-primary-hover rounded-xl transition-colors">
                            {{ __('landing.register') }}
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center gap-2">
                    <div class="flex items-center gap-1">
                        @if(app()->getLocale() === 'ko')
                            <a href="{{ url('/en') }}" class="px-2 py-1 text-xs text-content-secondary rounded">EN</a>
                            <span class="px-2 py-1 text-xs font-medium text-accent bg-accent-light rounded">KO</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium text-accent bg-accent-light rounded">EN</span>
                            <a href="{{ url('/') }}" class="px-2 py-1 text-xs text-content-secondary rounded">KO</a>
                        @endif
                    </div>
                    <button type="button" class="mobile-menu-button p-2 rounded-lg text-content-secondary hover:text-content hover:bg-surface-subtle transition-colors" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="menu-icon w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                        <svg class="close-icon hidden w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu hidden md:hidden bg-white border-t border-border">
            <div class="px-4 py-3 space-y-1">
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 text-base font-medium text-content hover:bg-surface-subtle rounded-lg transition-colors">
                        {{ __('landing.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('guest.analyses') }}" class="block px-4 py-3 text-base text-content hover:bg-surface-subtle rounded-lg transition-colors">
                        {{ __('guest.my_analyses') }}
                    </a>
                    <a href="{{ route('login') }}" class="block px-4 py-3 text-base text-content hover:bg-surface-subtle rounded-lg transition-colors">
                        {{ __('landing.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="block px-4 py-3 text-base font-medium text-white bg-primary hover:bg-primary-hover rounded-xl text-center transition-colors mt-2">
                        {{ __('landing.register') }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative bg-white pt-32 pb-20 sm:pt-40 sm:pb-28 overflow-hidden">
        <!-- Subtle gradient background -->
        <div class="absolute inset-0 bg-gradient-to-b from-accent-light/30 to-transparent pointer-events-none"></div>

        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-primary tracking-tight mb-6 leading-tight">
                {{ __('landing.hero_title') }}
                <span class="text-accent">{{ __('landing.hero_title_highlight') }}</span>
            </h1>
            <p class="text-lg sm:text-xl text-content-secondary max-w-2xl mx-auto mb-10 leading-relaxed">
                {{ __('landing.hero_subtitle') }}
            </p>

            <!-- URL Input Form -->
            <div class="max-w-2xl mx-auto">
                @guest
                    @if($usageInfo && $usageInfo['remaining'] <= 1)
                        <div class="mb-4 p-4 bg-warning-light border border-warning/20 rounded-xl">
                            <div class="flex items-center justify-center gap-2 text-warning-dark text-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                @if($usageInfo['remaining'] > 0)
                                    {{ __('landing.daily_limit_warning', ['remaining' => $usageInfo['remaining']]) }}
                                @else
                                    {{ __('landing.daily_limit_exceeded', ['limit' => $usageInfo['limit']]) }}
                                @endif
                            </div>
                        </div>
                    @endif
                @endguest

                <div class="bg-white rounded-2xl shadow-soft border border-border p-2">
                    <form id="seo-analyze-form" class="flex flex-col sm:flex-row gap-2">
                        <input
                            type="url"
                            id="website-url"
                            placeholder="{{ __('landing.url_placeholder') }}"
                            required
                            class="flex-1 px-5 py-4 text-base sm:text-lg border-0 bg-surface-subtle rounded-xl text-content placeholder-content-muted focus:ring-2 focus:ring-accent focus:bg-white transition-all"
                        >
                        <button
                            type="submit"
                            id="analyze-button"
                            class="px-8 py-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-xl transition-all disabled:bg-content-muted disabled:cursor-not-allowed whitespace-nowrap"
                            @guest @if($usageInfo && $usageInfo['remaining'] <= 0) disabled @endif @endguest
                        >
                            @guest
                                @if($usageInfo && $usageInfo['remaining'] <= 0)
                                    {{ __('landing.unlimited_with_signup') }}
                                @else
                                    {{ __('landing.analyze_button') }}
                                @endif
                            @else
                                {{ __('landing.analyze_button') }}
                            @endguest
                        </button>
                    </form>
                </div>

                <!-- Usage info -->
                <div class="mt-6 space-y-3">
                    @guest
                        @if($usageInfo)
                            <div class="flex justify-center items-center gap-4 text-sm text-content-secondary">
                                <span>{{ __('landing.daily_limit_info', ['used' => $usageInfo['used'], 'limit' => $usageInfo['limit']]) }}</span>
                                <span class="text-content-muted">{{ __('landing.reset_tomorrow', ['time' => $usageInfo['reset_time']->format('H:i')]) }}</span>
                            </div>
                            <div class="max-w-xs mx-auto h-1.5 bg-surface-subtle rounded-full overflow-hidden">
                                <div class="h-full bg-accent rounded-full transition-all duration-500" style="width: {{ min(($usageInfo['used'] / $usageInfo['limit']) * 100, 100) }}%"></div>
                            </div>
                        @endif
                    @endguest
                    <p class="text-sm text-content-muted">
                        @auth
                            {{ __('analysis.unlimited_for_members') }} &middot; {{ __('landing.hero_features') }}
                        @else
                            {{ __('landing.hero_features') }}
                        @endauth
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 sm:py-28 bg-surface-muted">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-primary mb-4">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="text-lg text-content-secondary max-w-2xl mx-auto">
                    {{ __('landing.features_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-accent-light rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_comprehensive_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_comprehensive_desc') }}</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-success-light rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_diagnosis_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_diagnosis_desc') }}</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-warning-light rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_recommendations_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_recommendations_desc') }}</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_realtime_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_realtime_desc') }}</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-error-light rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_pdf_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_pdf_desc') }}</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white rounded-2xl p-8 border border-border hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-3">{{ __('landing.feature_history_title') }}</h3>
                    <p class="text-content-secondary leading-relaxed">{{ __('landing.feature_history_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 sm:py-28 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-primary mb-10">
                        {{ __('landing.benefits_title') }}
                        <span class="text-accent">{{ __('landing.benefits_title_highlight') }}</span>
                    </h2>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-success-light rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-1">{{ __('landing.benefit_expert_title') }}</h3>
                                <p class="text-content-secondary">{{ __('landing.benefit_expert_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-success-light rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-1">{{ __('landing.benefit_easy_title') }}</h3>
                                <p class="text-content-secondary">{{ __('landing.benefit_easy_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-success-light rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-1">{{ __('landing.benefit_monitoring_title') }}</h3>
                                <p class="text-content-secondary">{{ __('landing.benefit_monitoring_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-success-light rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-1">{{ __('landing.benefit_free_title') }}</h3>
                                <p class="text-content-secondary">{{ __('landing.benefit_free_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 lg:mt-0">
                    <div class="bg-primary rounded-2xl p-8 sm:p-10 text-white">
                        <h3 class="text-2xl font-bold mb-8">{{ __('landing.cta_box_title') }}</h3>

                        @guest
                            @if($usageInfo)
                                <div class="bg-white/10 rounded-xl p-4 mb-8">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium">{{ __('landing.daily_limit_info', ['used' => $usageInfo['used'], 'limit' => $usageInfo['limit']]) }}</span>
                                        <span class="text-xs bg-white/20 px-2 py-1 rounded-lg">{{ __('analysis.unlimited_for_members') }}</span>
                                    </div>
                                    <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                        <div class="h-full bg-white rounded-full transition-all duration-300" style="width: {{ min(($usageInfo['used'] / $usageInfo['limit']) * 100, 100) }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endguest

                        <div class="space-y-4 mb-8">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ __('landing.cta_box_feature1') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ __('landing.cta_box_feature2') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ __('landing.cta_box_feature3') }}</span>
                            </div>
                            @guest
                                <div class="flex items-center gap-3 pt-4 border-t border-white/20">
                                    <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-medium">{{ __('landing.unlimited_with_signup') }}</span>
                                </div>
                            @endguest
                        </div>

                        @guest
                            <a href="{{ route('register') }}" class="block w-full bg-white text-primary font-semibold py-4 px-6 rounded-xl text-center hover:bg-surface-subtle transition-colors">
                                {{ __('landing.cta_box_button_guest') }}
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="block w-full bg-white text-primary font-semibold py-4 px-6 rounded-xl text-center hover:bg-surface-subtle transition-colors">
                                {{ __('landing.cta_box_button_auth') }}
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 sm:py-28 bg-primary">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                {{ __('landing.final_cta_title') }}
            </h2>
            <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                {{ __('landing.final_cta_subtitle') }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-primary font-semibold rounded-xl hover:bg-surface-subtle transition-colors">
                        {{ __('landing.final_cta_button_start') }}
                    </a>
                    <a href="#seo-analyze-form" class="px-8 py-4 border border-white/30 text-white font-semibold rounded-xl hover:bg-white/10 transition-colors">
                        {{ __('landing.final_cta_button_analyze') }}
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-white text-primary font-semibold rounded-xl hover:bg-surface-subtle transition-colors">
                        {{ __('landing.final_cta_button_dashboard') }}
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-border">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                <div class="md:col-span-2">
                    <h3 class="text-xl font-bold text-primary mb-4">SEO Validator</h3>
                    <p class="text-content-secondary max-w-md leading-relaxed">
                        {{ __('landing.footer_description') }}
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-primary uppercase tracking-wider mb-4">{{ __('landing.footer_services') }}</h4>
                    <ul class="space-y-3 text-content-secondary">
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_seo_analysis') }}</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_pdf_report') }}</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_history_management') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-primary uppercase tracking-wider mb-4">{{ __('landing.footer_company') }}</h4>
                    <ul class="space-y-3 text-content-secondary">
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_terms') }}</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_privacy') }}</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">{{ __('landing.footer_contact') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-border mt-12 pt-8 text-center">
                <p class="text-content-muted text-sm">
                    {{ __('landing.footer_copyright') }}
                </p>
            </div>
        </div>
    </footer>

    <script>
        // SEO Analyze Form
        document.getElementById('seo-analyze-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = document.getElementById('analyze-button');
            const url = document.getElementById('website-url').value;

            if (!url) return;

            @auth
                window.location.href = `{{ route('dashboard') }}?analyze=${encodeURIComponent(url)}`;
            @else
                @if($usageInfo && $usageInfo['remaining'] <= 0)
                    window.location.href = `{{ route('register') }}?analyze=${encodeURIComponent(url)}`;
                @else
                    button.disabled = true;
                    button.textContent = '{{ __('analysis.analyzing') }}...';

                    fetch('{{ route('guest.analyze') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ url: url })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            if (data.action_required === 'register') {
                                alert(data.message);
                                window.location.href = `{{ route('register') }}?analyze=${encodeURIComponent(url)}`;
                                return;
                            }
                            throw new Error(data.message);
                        }
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = '{{ route('guest.analyses') }}';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('{{ __('analysis.error_occurred') }}: ' + error.message);
                        button.disabled = false;
                        button.textContent = '{{ __('landing.analyze_button') }}';
                    });
                @endif
            @endauth
        });

        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.querySelector('.mobile-menu');
            const menuIcon = mobileMenuButton.querySelector('.menu-icon');
            const closeIcon = mobileMenuButton.querySelector('.close-icon');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    const isOpen = !mobileMenu.classList.contains('hidden');

                    if (isOpen) {
                        mobileMenu.classList.add('hidden');
                        menuIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    } else {
                        mobileMenu.classList.remove('hidden');
                        menuIcon.classList.add('hidden');
                        closeIcon.classList.remove('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'true');
                    }
                });

                document.addEventListener('click', function(event) {
                    if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                        mobileMenu.classList.add('hidden');
                        menuIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });

        window.addEventListener('resize', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            if (window.innerWidth >= 768 && mobileMenu && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                const mobileMenuButton = document.querySelector('.mobile-menu-button');
                const menuIcon = mobileMenuButton.querySelector('.menu-icon');
                const closeIcon = mobileMenuButton.querySelector('.close-icon');
                menuIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>
