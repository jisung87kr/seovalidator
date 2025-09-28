<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEO Validator - 무료 웹사이트 SEO 분석 도구</title>
    <meta name="description" content="SEO Validator로 웹사이트의 검색엔진 최적화 상태를 무료로 분석하세요. 상세한 리포트와 개선 방안을 제공합니다.">
    <meta name="keywords" content="SEO 분석, 웹사이트 최적화, 검색엔진 최적화, SEO 도구, 무료 SEO 분석">

    <!-- Open Graph -->
    <meta property="og:title" content="SEO Validator - 무료 웹사이트 SEO 분석 도구">
    <meta property="og:description" content="웹사이트의 SEO 상태를 즉시 분석하고 상세한 개선 방안을 받아보세요.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SEO Validator - 무료 웹사이트 SEO 분석 도구">
    <meta name="twitter:description" content="웹사이트의 SEO 상태를 즉시 분석하고 상세한 개선 방안을 받아보세요.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional Styles -->
    <style>
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
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
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
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            대시보드
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                            로그인
                        </a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            회원가입
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
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    웹사이트 SEO를
                    <br>
                    <span class="text-yellow-300">무료로 분석</span>하세요
                </h1>
                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
                    단 몇 분만에 웹사이트의 검색엔진 최적화 상태를 분석하고
                    상세한 개선 방안을 받아보세요
                </p>

                <!-- URL Input Form -->
                <div class="max-w-2xl mx-auto mb-12">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                        <form id="seo-analyze-form" class="flex flex-col sm:flex-row gap-4">
                            <input
                                type="url"
                                id="website-url"
                                placeholder="https://example.com"
                                required
                                class="flex-1 px-6 py-4 rounded-lg border-0 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-yellow-300 text-lg"
                            >
                            <button
                                type="submit"
                                class="px-8 py-4 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold rounded-lg transition-colors text-lg"
                            >
                                무료 분석 시작
                            </button>
                        </form>
                        <p class="text-white/80 text-sm mt-4">
                            ✓ 완전 무료 ✓ 회원가입 불필요 ✓ 즉시 결과 확인
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="text-center">
                        <div class="stats-counter text-white">50K+</div>
                        <p class="text-white/80">분석 완료</p>
                    </div>
                    <div class="text-center">
                        <div class="stats-counter text-white">98%</div>
                        <p class="text-white/80">정확도</p>
                    </div>
                    <div class="text-center">
                        <div class="stats-counter text-white">24/7</div>
                        <p class="text-white/80">서비스 제공</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    강력한 SEO 분석 기능
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    전문적인 SEO 분석 도구로 웹사이트의 모든 측면을 꼼꼼히 검사합니다
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
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">종합 SEO 점수</h3>
                    <p class="text-gray-600">
                        기술적 SEO, 콘텐츠 품질, 성능, 접근성을 종합한 정확한 SEO 점수를 제공합니다.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">상세한 문제 진단</h3>
                    <p class="text-gray-600">
                        메타 태그, 제목, 이미지 alt 속성 등 SEO에 영향을 미치는 모든 요소를 자세히 분석합니다.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">실행 가능한 개선안</h3>
                    <p class="text-gray-600">
                        단순한 문제 지적이 아닌, 구체적이고 실행 가능한 개선 방안을 제시합니다.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">실시간 분석</h3>
                    <p class="text-gray-600">
                        URL 입력 후 몇 분 내에 완전한 SEO 분석 결과를 확인할 수 있습니다.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">PDF 리포트 다운로드</h3>
                    <p class="text-gray-600">
                        분석 결과를 깔끔한 PDF 리포트로 다운로드하여 팀과 공유하거나 보관할 수 있습니다.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-xl p-8 shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">히스토리 관리</h3>
                    <p class="text-gray-600">
                        과거 분석 결과를 저장하고 비교하여 SEO 개선 진행 상황을 추적할 수 있습니다.
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
                        왜 SEO Validator를
                        <span class="text-indigo-600">선택해야 할까요?</span>
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
                                <h3 class="text-lg font-semibold text-gray-900">전문가 수준의 분석</h3>
                                <p class="text-gray-600">SEO 전문가들이 사용하는 기준으로 웹사이트를 분석합니다.</p>
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
                                <h3 class="text-lg font-semibold text-gray-900">사용하기 쉬운 인터페이스</h3>
                                <p class="text-gray-600">복잡한 설정 없이 URL만 입력하면 즉시 분석이 시작됩니다.</p>
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
                                <h3 class="text-lg font-semibold text-gray-900">지속적인 모니터링</h3>
                                <p class="text-gray-600">정기적으로 분석하여 SEO 성과 변화를 추적할 수 있습니다.</p>
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
                                <h3 class="text-lg font-semibold text-gray-900">완전 무료</h3>
                                <p class="text-gray-600">숨겨진 비용이나 제한 없이 모든 기능을 무료로 사용할 수 있습니다.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 lg:mt-0">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6">지금 바로 시작하세요!</h3>
                        <div class="space-y-4 mb-8">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                URL 입력 후 1분 내 결과 확인
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                20개 이상의 SEO 요소 분석
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                PDF 리포트 무료 다운로드
                            </div>
                        </div>
                        @guest
                            <a href="{{ route('register') }}" class="block w-full bg-white text-indigo-600 font-semibold py-3 px-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                                무료 회원가입
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="block w-full bg-white text-indigo-600 font-semibold py-3 px-6 rounded-lg text-center hover:bg-gray-50 transition-colors">
                                대시보드로 이동
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
                웹사이트의 SEO를 개선할 준비가 되셨나요?
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                지금 바로 분석을 시작하고 검색 엔진에서 더 높은 순위를 차지해보세요
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center max-w-md mx-auto">
                @guest
                    <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        무료로 시작하기
                    </a>
                    <a href="#seo-analyze-form" class="border border-gray-300 text-gray-300 hover:text-white hover:border-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        바로 분석하기
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                        대시보드로 이동
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
                        웹사이트의 검색엔진 최적화 상태를 분석하고 개선 방안을 제공하는
                        무료 SEO 분석 도구입니다.
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">서비스</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li><a href="#" class="hover:text-gray-900">SEO 분석</a></li>
                        <li><a href="#" class="hover:text-gray-900">PDF 리포트</a></li>
                        <li><a href="#" class="hover:text-gray-900">히스토리 관리</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">회사</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li><a href="#" class="hover:text-gray-900">이용약관</a></li>
                        <li><a href="#" class="hover:text-gray-900">개인정보처리방침</a></li>
                        <li><a href="#" class="hover:text-gray-900">문의하기</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-200 mt-8 pt-8 text-center">
                <p class="text-gray-600">
                    © 2025 SEO Validator. All rights reserved.
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