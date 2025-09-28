<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
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

    <!-- Additional Styles -->
    <style>

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            background-color: #f9fafb;
            color: #111827;
            word-break: keep-all;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stats-counter {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4f46e5;
        }

        /* Mobile-specific optimizations */
        @media (max-width: 768px) {
            .break-keep {
                word-break: keep-all;
                line-height: 1.6;
            }

            /* Better focus states for mobile */
            button:focus, a:focus, input:focus {
                outline: 3px solid #3b82f6;
                outline-offset: 2px;
            }

            /* Prevent zoom on input focus */
            input[type="url"], input[type="text"], input[type="email"] {
                font-size: 16px;
            }

            /* Better tap targets */
            .mobile-menu a {
                padding: 12px 16px;
                display: block;
            }

            /* Smooth transitions */
            .mobile-menu {
                transition: all 0.3s ease-in-out;
            }
        }

        /* Tablet optimizations */
        @media (min-width: 769px) and (max-width: 1024px) {
            .hero-pattern {
                padding-top: 5rem;
                padding-bottom: 5rem;
            }
        }
    </style>
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">SEO Validator</h1>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Language Switcher -->
                    <div class="flex items-center space-x-2">
                        @if(app()->getLocale() === 'ko')
                            <a href="{{ url('/en') }}" class="text-gray-500 hover:text-gray-700 px-2 py-1 text-sm font-medium border border-gray-300 rounded">
                                EN
                            </a>
                            <span class="text-indigo-600 px-2 py-1 text-sm font-medium border border-indigo-600 rounded bg-indigo-50">
                                한국어
                            </span>
                        @else
                            <span class="text-indigo-600 px-2 py-1 text-sm font-medium border border-indigo-600 rounded bg-indigo-50">
                                EN
                            </span>
                            <a href="{{ url('/') }}" class="text-gray-500 hover:text-gray-700 px-2 py-1 text-sm font-medium border border-gray-300 rounded">
                                한국어
                            </a>
                        @endif
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            {{ __('landing.dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('guest.analyses') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            {{ __('guest.my_analyses') }}
                        </a>
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            {{ __('landing.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            {{ __('landing.register') }}
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center space-x-2">
                    <!-- Mobile Language Switcher -->
                    <div class="flex items-center space-x-1">
                        @if(app()->getLocale() === 'ko')
                            <a href="{{ url('/en') }}" class="text-gray-500 hover:text-gray-700 px-1 py-1 text-xs font-medium border border-gray-300 rounded">
                                EN
                            </a>
                            <span class="text-indigo-600 px-1 py-1 text-xs font-medium border border-indigo-600 rounded bg-indigo-50">
                                한국어
                            </span>
                        @else
                            <span class="text-indigo-600 px-1 py-1 text-xs font-medium border border-indigo-600 rounded bg-indigo-50">
                                EN
                            </span>
                            <a href="{{ url('/') }}" class="text-gray-500 hover:text-gray-700 px-1 py-1 text-xs font-medium border border-gray-300 rounded">
                                한국어
                            </a>
                        @endif
                    </div>

                    <button type="button" class="mobile-menu-button bg-gray-50 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900 block px-3 py-2 text-base font-medium">
                        {{ __('landing.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('guest.analyses') }}" class="text-gray-700 hover:text-gray-900 block px-3 py-2 text-base font-medium">
                        {{ __('guest.my_analyses') }}
                    </a>
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 block px-3 py-2 text-base font-medium">
                        {{ __('landing.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white block px-3 py-2 rounded-md text-base font-medium mt-2">
                        {{ __('landing.register') }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg hero-pattern pt-16 sm:pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl md:text-6xl font-bold text-white mb-4 sm:mb-6 leading-tight" style="font-weight: 900">
                    {{ __('landing.hero_title') }}
                    <br class="hidden sm:block">
                    <span class="text-yellow-300">{{ __('landing.hero_title_highlight') }}</span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-white/90 mb-6 sm:mb-8 max-w-3xl mx-auto break-keep px-4 sm:px-0">
                    {{ __('landing.hero_subtitle') }}
                </p>

                <!-- URL Input Form -->
                <div class="max-w-2xl mx-auto mb-8 sm:mb-12">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 sm:p-6">
                        @guest
                            @if($usageInfo && $usageInfo['remaining'] <= 1)
                                <div class="mb-4 p-3 bg-orange-500/20 border border-orange-300/30 rounded-lg">
                                    <div class="flex items-center text-white text-sm">
                                        <svg class="w-5 h-5 mr-2 text-orange-300" fill="currentColor" viewBox="0 0 20 20">
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

                        <form id="seo-analyze-form" class="flex flex-col gap-3 sm:gap-4">
                            <input
                                type="url"
                                id="website-url"
                                placeholder="{{ __('landing.url_placeholder') }}"
                                required
                                class="w-full px-4 sm:px-6 py-3 sm:py-4 rounded-lg border-0 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-yellow-300 text-base sm:text-lg"
                            >
                            <button
                                type="submit"
                                id="analyze-button"
                                class="w-full px-6 sm:px-8 py-3 sm:py-4 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold rounded-lg transition-colors text-base sm:text-lg disabled:bg-gray-400 disabled:cursor-not-allowed"
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

                        <div class="mt-4 space-y-2">
                            @guest
                                @if($usageInfo)
                                    <div class="flex justify-between items-center text-white/80 text-sm">
                                        <span>{{ __('landing.daily_limit_info', ['used' => $usageInfo['used'], 'limit' => $usageInfo['limit']]) }}</span>
                                        <span>{{ __('landing.reset_tomorrow', ['time' => $usageInfo['reset_time']->format('H:i')]) }}</span>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2">
                                        <div class="bg-yellow-400 h-2 rounded-full transition-all duration-500" style="width: {{ ($usageInfo['used'] / $usageInfo['limit']) * 100 }}%"></div>
                                    </div>
                                @endif
                            @endguest
                            <p class="text-white/80 text-sm">
                                @auth
                                    ✓ {{ __('analysis.unlimited_for_members') }} ✓ {{ __('landing.hero_features') }}
                                @else
                                    {{ __('landing.hero_features') }}
                                @endauth
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
{{--                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">--}}
{{--                    <div class="text-center">--}}
{{--                        <div class="stats-counter text-white">50K+</div>--}}
{{--                        <p class="text-white/80">분석 완료</p>--}}
{{--                    </div>--}}
{{--                    <div class="text-center">--}}
{{--                        <div class="stats-counter text-white">98%</div>--}}
{{--                        <p class="text-white/80">정확도</p>--}}
{{--                    </div>--}}
{{--                    <div class="text-center">--}}
{{--                        <div class="stats-counter text-white">24/7</div>--}}
{{--                        <p class="text-white/80">서비스 제공</p>--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-12 sm:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto px-4 sm:px-0">
                    {{ __('landing.features_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_comprehensive_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_comprehensive_desc') }}
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_diagnosis_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_diagnosis_desc') }}
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_recommendations_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_recommendations_desc') }}
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_realtime_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_realtime_desc') }}
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_pdf_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_pdf_desc') }}
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-xl p-6 sm:p-8 shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('landing.feature_history_title') }}</h3>
                    <p class="text-gray-600">
                        {{ __('landing.feature_history_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8">
                        {{ __('landing.benefits_title') }}
                        <span class="text-indigo-600">{{ __('landing.benefits_title_highlight') }}</span>
                    </h2>

                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('landing.benefit_expert_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.benefit_expert_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('landing.benefit_easy_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.benefit_easy_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('landing.benefit_monitoring_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.benefit_monitoring_desc') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('landing.benefit_free_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.benefit_free_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 lg:mt-0">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6">{{ __('landing.cta_box_title') }}</h3>

                        @guest
                            @if($usageInfo)
                                <!-- Usage limit reminder for guests -->
                                <div class="bg-white/10 rounded-lg p-4 mb-6 border border-white/20">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium">{{ __('landing.daily_limit_info', ['used' => $usageInfo['used'], 'limit' => $usageInfo['limit']]) }}</span>
                                        <span class="text-xs bg-white/20 px-2 py-1 rounded">{{ __('analysis.unlimited_for_members') }}</span>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2">
                                        <div class="bg-white rounded-full h-2 transition-all duration-300" style="width: {{ ($usageInfo['used'] / $usageInfo['limit']) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endguest

                        <div class="space-y-4 mb-8">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('landing.cta_box_feature1') }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('landing.cta_box_feature2') }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('landing.cta_box_feature3') }}
                            </div>
                            @guest
                                <div class="flex items-center border-t border-white/20 pt-4">
                                    <svg class="w-5 h-5 mr-3 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-medium">{{ __('landing.unlimited_with_signup') }}</span>
                                </div>
                            @endguest
                        </div>
                        @guest
                            <a href="{{ route('register') }}" class="block w-full bg-white text-indigo-600 font-semibold py-3 px-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                                {{ __('landing.cta_box_button_guest') }}
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="block w-full bg-white text-indigo-600 font-semibold py-3 px-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                                {{ __('landing.cta_box_button_auth') }}
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                {{ __('landing.final_cta_title') }}
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                {{ __('landing.final_cta_subtitle') }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center max-w-md mx-auto">
                @guest
                    <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        {{ __('landing.final_cta_button_start') }}
                    </a>
                    <a href="#seo-analyze-form" class="border border-gray-300 text-gray-300 hover:text-white hover:border-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        {{ __('landing.final_cta_button_analyze') }}
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        {{ __('landing.final_cta_button_dashboard') }}
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">SEO Validator</h3>
                    <p class="text-gray-600 mb-4">
                        {{ __('landing.footer_description') }}
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('landing.footer_services') }}</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_seo_analysis') }}</a></li>
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_pdf_report') }}</a></li>
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_history_management') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('landing.footer_company') }}</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_terms') }}</a></li>
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_privacy') }}</a></li>
                        <li><a href="#" class="hover:text-gray-900">{{ __('landing.footer_contact') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-200 mt-8 pt-8 text-center">
                <p class="text-gray-600">
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
                // 로그인된 사용자는 분석 페이지로 이동
                window.location.href = `{{ route('dashboard') }}?analyze=${encodeURIComponent(url)}`;
            @else
                // 비로그인 사용자 처리
                @if($usageInfo && $usageInfo['remaining'] <= 0)
                    // 한도 초과 시 회원가입 페이지로 이동
                    window.location.href = `{{ route('register') }}?analyze=${encodeURIComponent(url)}`;
                @else
                    // 한도 내일 시 데모 분석 실행
                    button.disabled = true;
                    button.textContent = '분석 중...';

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

                            // 분석 시작됨 - 게스트 분석 페이지로 이동
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                // Fallback to guest analyses page
                                window.location.href = '{{ route('guest.analyses') }}';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('분석 시작 중 오류가 발생했습니다: ' + error.message);
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
            const hamburgerIcon = mobileMenuButton.querySelector('svg:first-child');
            const closeIcon = mobileMenuButton.querySelector('svg:last-child');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    const isOpen = !mobileMenu.classList.contains('hidden');

                    if (isOpen) {
                        // Close menu
                        mobileMenu.classList.add('hidden');
                        hamburgerIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    } else {
                        // Open menu
                        mobileMenu.classList.remove('hidden');
                        hamburgerIcon.classList.add('hidden');
                        closeIcon.classList.remove('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'true');
                    }
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                        mobileMenu.classList.add('hidden');
                        hamburgerIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Smooth scrolling for mobile
            if (window.innerWidth <= 768) {
                // Add touch-friendly scroll behavior
                document.documentElement.style.scrollBehavior = 'smooth';

                // Prevent horizontal scroll
                document.body.style.overflowX = 'hidden';
            }
        });

        // Handle viewport changes for responsive behavior
        window.addEventListener('resize', function() {
            const mobileMenu = document.querySelector('.mobile-menu');

            // Auto-close mobile menu on desktop resize
            if (window.innerWidth >= 768 && mobileMenu && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                const mobileMenuButton = document.querySelector('.mobile-menu-button');
                const hamburgerIcon = mobileMenuButton.querySelector('svg:first-child');
                const closeIcon = mobileMenuButton.querySelector('svg:last-child');

                hamburgerIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>
