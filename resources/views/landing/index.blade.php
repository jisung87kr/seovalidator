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
    </style>
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-900">SEO Validator</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
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
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            {{ __('landing.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            {{ __('landing.register') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg hero-pattern pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6" style="font-weight: 900">
                    {{ __('landing.hero_title') }}
                    <br>
                    <span class="text-yellow-300">{{ __('landing.hero_title_highlight') }}</span>
                </h1>
                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto break-keep">
                    {{ __('landing.hero_subtitle') }}
                </p>

                <!-- URL Input Form -->
                <div class="max-w-2xl mx-auto mb-12">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                        <form id="seo-analyze-form" class="flex flex-col sm:flex-row gap-4">
                            <input
                                type="url"
                                id="website-url"
                                placeholder="{{ __('landing.url_placeholder') }}"
                                required
                                class="flex-1 px-6 py-4 rounded-lg border-0 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-yellow-300 text-lg"
                            >
                            <button
                                type="submit"
                                class="px-8 py-4 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold rounded-lg transition-colors text-lg"
                            >
                                {{ __('landing.analyze_button') }}
                            </button>
                        </form>
                        <p class="text-white/80 text-sm mt-4">
                            {{ __('landing.hero_features') }}
                        </p>
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
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    {{ __('landing.features_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
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
        document.getElementById('seo-analyze-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const url = document.getElementById('website-url').value;
            if (url) {
                @auth
                    // 로그인된 사용자는 분석 페이지로 이동
                    window.location.href = `{{ route('dashboard') }}?analyze=${encodeURIComponent(url)}`;
                @else
                    // 비로그인 사용자는 회원가입 페이지로 이동
                    window.location.href = `{{ route('register') }}?analyze=${encodeURIComponent(url)}`;
                @endauth
            }
        });
    </script>
</body>
</html>
