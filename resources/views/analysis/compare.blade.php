@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ __('analysis.compare_title') }}</h1>
                <p class="mt-2 text-content-secondary">{{ __('analysis.compare_subtitle') }}</p>
            </div>
            <a href="{{ route('analysis.history') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-white border border-border hover:bg-surface-subtle text-content-secondary text-sm font-medium rounded-xl transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('analysis.back_to_history') }}
            </a>
        </div>
    </div>

    <!-- Selection Form -->
    <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 mb-8">
        <h2 class="text-lg font-semibold text-primary mb-6">{{ __('analysis.select_analyses_to_compare') }}</h2>
        <form method="GET" action="{{ route('analysis.compare') }}" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:gap-4">
            <div class="flex-1">
                <label for="analysis1" class="block text-sm font-medium text-content mb-2">{{ __('analysis.first_analysis') }}</label>
                <select id="analysis1" name="analysis1"
                        class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                    <option value="">{{ __('analysis.select_an_analysis') }}</option>
                    @foreach($availableAnalyses as $analysis)
                        <option value="{{ $analysis->id }}" {{ request('analysis1') == $analysis->id ? 'selected' : '' }}>
                            {{ $analysis->url }} ({{ $analysis->overall_score ? number_format($analysis->overall_score, 0) : '--' }}) - {{ $analysis->created_at->format('M j, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1">
                <label for="analysis2" class="block text-sm font-medium text-content mb-2">{{ __('analysis.second_analysis') }}</label>
                <select id="analysis2" name="analysis2"
                        class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                    <option value="">{{ __('analysis.select_an_analysis') }}</option>
                    @foreach($availableAnalyses as $analysis)
                        <option value="{{ $analysis->id }}" {{ request('analysis2') == $analysis->id ? 'selected' : '' }}>
                            {{ $analysis->url }} ({{ $analysis->overall_score ? number_format($analysis->overall_score, 0) : '--' }}) - {{ $analysis->created_at->format('M j, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-accent hover:bg-accent-hover text-white text-sm font-medium rounded-xl transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                {{ __('analysis.compare_button') }}
            </button>
        </form>
    </div>

    @if($comparison)
        <!-- Comparison Results -->
        <div class="space-y-6">
            <!-- Score Comparison -->
            <div class="bg-white rounded-2xl border border-border overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-border">
                    <h2 class="text-lg font-semibold text-primary">{{ __('analysis.score_comparison') }}</h2>
                </div>
                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Analysis 1 -->
                        <div class="text-center">
                            <p class="text-sm font-mono text-content-secondary mb-4 truncate" title="{{ $comparison['analysis1']->url }}">
                                {{ $comparison['analysis1']->url }}
                            </p>
                            <div class="relative w-28 h-28 mx-auto mb-4">
                                @php
                                    $score1 = $comparison['analysis1']->overall_score;
                                    $color1 = $score1 >= 90 ? 'success' : ($score1 >= 70 ? 'accent' : ($score1 >= 50 ? 'warning' : 'error'));
                                @endphp
                                <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" class="text-surface-subtle"></circle>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                                            class="text-{{ $color1 }}"
                                            style="stroke-dasharray: {{ 2 * pi() * 50 }}; stroke-dashoffset: {{ 2 * pi() * 50 * (1 - $score1 / 100) }}"></circle>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold text-primary">{{ number_format($score1, 0) }}</span>
                                    <span class="text-xs text-content-muted">/100</span>
                                </div>
                            </div>
                            <div class="space-y-3 max-w-xs mx-auto">
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.technical_seo') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis1']->technical_score ? number_format($comparison['analysis1']->technical_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.content') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis1']->content_score ? number_format($comparison['analysis1']->content_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.performance') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis1']->performance_score ? number_format($comparison['analysis1']->performance_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.accessibility') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis1']->accessibility_score ? number_format($comparison['analysis1']->accessibility_score, 0) : '--' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Analysis 2 -->
                        <div class="text-center">
                            <p class="text-sm font-mono text-content-secondary mb-4 truncate" title="{{ $comparison['analysis2']->url }}">
                                {{ $comparison['analysis2']->url }}
                            </p>
                            <div class="relative w-28 h-28 mx-auto mb-4">
                                @php
                                    $score2 = $comparison['analysis2']->overall_score;
                                    $color2 = $score2 >= 90 ? 'success' : ($score2 >= 70 ? 'accent' : ($score2 >= 50 ? 'warning' : 'error'));
                                @endphp
                                <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" class="text-surface-subtle"></circle>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                                            class="text-{{ $color2 }}"
                                            style="stroke-dasharray: {{ 2 * pi() * 50 }}; stroke-dashoffset: {{ 2 * pi() * 50 * (1 - $score2 / 100) }}"></circle>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold text-primary">{{ number_format($score2, 0) }}</span>
                                    <span class="text-xs text-content-muted">/100</span>
                                </div>
                            </div>
                            <div class="space-y-3 max-w-xs mx-auto">
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.technical_seo') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis2']->technical_score ? number_format($comparison['analysis2']->technical_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.content') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis2']->content_score ? number_format($comparison['analysis2']->content_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.performance') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis2']->performance_score ? number_format($comparison['analysis2']->performance_score, 0) : '--' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-content-secondary">{{ __('analysis.accessibility') }}</span>
                                    <span class="font-medium text-primary">{{ $comparison['analysis2']->accessibility_score ? number_format($comparison['analysis2']->accessibility_score, 0) : '--' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score Differences -->
                    <div class="mt-8 pt-8 border-t border-border">
                        <h3 class="text-base font-semibold text-primary mb-6 text-center">{{ __('analysis.score_differences') }}</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @php $diff = $comparison['analysis2']->overall_score - $comparison['analysis1']->overall_score; @endphp
                            <div class="bg-surface-subtle rounded-xl p-4 text-center">
                                <div class="text-sm text-content-secondary mb-1">{{ __('analysis.overall') }}</div>
                                <div class="text-xl font-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-error' : 'text-content-muted') }}">
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0) }}
                                </div>
                            </div>

                            @php $techDiff = ($comparison['analysis2']->technical_score ?? 0) - ($comparison['analysis1']->technical_score ?? 0); @endphp
                            <div class="bg-surface-subtle rounded-xl p-4 text-center">
                                <div class="text-sm text-content-secondary mb-1">{{ __('analysis.technical_seo') }}</div>
                                <div class="text-xl font-bold {{ $techDiff > 0 ? 'text-success' : ($techDiff < 0 ? 'text-error' : 'text-content-muted') }}">
                                    {{ $techDiff > 0 ? '+' : '' }}{{ number_format($techDiff, 0) }}
                                </div>
                            </div>

                            @php $contentDiff = ($comparison['analysis2']->content_score ?? 0) - ($comparison['analysis1']->content_score ?? 0); @endphp
                            <div class="bg-surface-subtle rounded-xl p-4 text-center">
                                <div class="text-sm text-content-secondary mb-1">{{ __('analysis.content') }}</div>
                                <div class="text-xl font-bold {{ $contentDiff > 0 ? 'text-success' : ($contentDiff < 0 ? 'text-error' : 'text-content-muted') }}">
                                    {{ $contentDiff > 0 ? '+' : '' }}{{ number_format($contentDiff, 0) }}
                                </div>
                            </div>

                            @php $perfDiff = ($comparison['analysis2']->performance_score ?? 0) - ($comparison['analysis1']->performance_score ?? 0); @endphp
                            <div class="bg-surface-subtle rounded-xl p-4 text-center">
                                <div class="text-sm text-content-secondary mb-1">{{ __('analysis.performance') }}</div>
                                <div class="text-xl font-bold {{ $perfDiff > 0 ? 'text-success' : ($perfDiff < 0 ? 'text-error' : 'text-content-muted') }}">
                                    {{ $perfDiff > 0 ? '+' : '' }}{{ number_format($perfDiff, 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Information Comparison -->
            @if(isset($comparison['data1']['seo_elements']['meta']) && isset($comparison['data2']['seo_elements']['meta']))
                <div class="bg-white rounded-2xl border border-border overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-border">
                        <h2 class="text-lg font-semibold text-primary">{{ __('analysis.page_info_comparison') }}</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Analysis 1 Meta -->
                            <div>
                                <h3 class="text-sm font-semibold text-primary mb-4">{{ parse_url($comparison['analysis1']->url, PHP_URL_HOST) }}</h3>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.title') }}</h4>
                                        <div class="bg-surface-subtle rounded-xl p-4">
                                            <p class="text-sm text-primary break-words">
                                                {{ $comparison['data1']['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                                            </p>
                                            <p class="text-xs text-content-muted mt-2">
                                                {{ __('analysis.length') }}: {{ $comparison['data1']['seo_elements']['meta']['title_length'] ?? 0 }} {{ __('analysis.characters') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.description') }}</h4>
                                        <div class="bg-surface-subtle rounded-xl p-4">
                                            <p class="text-sm text-primary break-words">
                                                {{ $comparison['data1']['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                                            </p>
                                            <p class="text-xs text-content-muted mt-2">
                                                {{ __('analysis.length') }}: {{ $comparison['data1']['seo_elements']['meta']['description_length'] ?? 0 }} {{ __('analysis.characters') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Analysis 2 Meta -->
                            <div>
                                <h3 class="text-sm font-semibold text-primary mb-4">{{ parse_url($comparison['analysis2']->url, PHP_URL_HOST) }}</h3>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.title') }}</h4>
                                        <div class="bg-surface-subtle rounded-xl p-4">
                                            <p class="text-sm text-primary break-words">
                                                {{ $comparison['data2']['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                                            </p>
                                            <p class="text-xs text-content-muted mt-2">
                                                {{ __('analysis.length') }}: {{ $comparison['data2']['seo_elements']['meta']['title_length'] ?? 0 }} {{ __('analysis.characters') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.description') }}</h4>
                                        <div class="bg-surface-subtle rounded-xl p-4">
                                            <p class="text-sm text-primary break-words">
                                                {{ $comparison['data2']['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                                            </p>
                                            <p class="text-xs text-content-muted mt-2">
                                                {{ __('analysis.length') }}: {{ $comparison['data2']['seo_elements']['meta']['description_length'] ?? 0 }} {{ __('analysis.characters') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Technical Comparison -->
            @if(isset($comparison['data1']['crawl_data']) && isset($comparison['data2']['crawl_data']))
                <div class="bg-white rounded-2xl border border-border overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-border">
                        <h2 class="text-lg font-semibold text-primary">{{ __('analysis.technical_comparison') }}</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Page Size -->
                            <div class="bg-surface-subtle rounded-xl p-4">
                                <h3 class="text-sm font-medium text-content-secondary mb-4">{{ __('analysis.page_size') }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_1') }}</span>
                                        <span class="font-medium text-primary">{{ isset($comparison['data1']['crawl_data']['html_size']) ? number_format($comparison['data1']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_2') }}</span>
                                        <span class="font-medium text-primary">{{ isset($comparison['data2']['crawl_data']['html_size']) ? number_format($comparison['data2']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}</span>
                                    </div>
                                    @if(isset($comparison['data1']['crawl_data']['html_size']) && isset($comparison['data2']['crawl_data']['html_size']))
                                        @php $sizeDiff = ($comparison['data2']['crawl_data']['html_size'] - $comparison['data1']['crawl_data']['html_size']) / 1024; @endphp
                                        <div class="flex justify-between text-sm pt-2 border-t border-border">
                                            <span class="text-content-muted">{{ __('analysis.difference') }}</span>
                                            <span class="font-medium {{ $sizeDiff > 0 ? 'text-error' : ($sizeDiff < 0 ? 'text-success' : 'text-content-muted') }}">
                                                {{ $sizeDiff > 0 ? '+' : '' }}{{ number_format($sizeDiff, 1) }} KB
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Load Time -->
                            <div class="bg-surface-subtle rounded-xl p-4">
                                <h3 class="text-sm font-medium text-content-secondary mb-4">{{ __('analysis.load_time') }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_1') }}</span>
                                        <span class="font-medium text-primary">{{ isset($comparison['data1']['crawl_data']['load_time_ms']) ? number_format($comparison['data1']['crawl_data']['load_time_ms']) . ' ms' : '--' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_2') }}</span>
                                        <span class="font-medium text-primary">{{ isset($comparison['data2']['crawl_data']['load_time_ms']) ? number_format($comparison['data2']['crawl_data']['load_time_ms']) . ' ms' : '--' }}</span>
                                    </div>
                                    @if(isset($comparison['data1']['crawl_data']['load_time_ms']) && isset($comparison['data2']['crawl_data']['load_time_ms']))
                                        @php $timeDiff = $comparison['data2']['crawl_data']['load_time_ms'] - $comparison['data1']['crawl_data']['load_time_ms']; @endphp
                                        <div class="flex justify-between text-sm pt-2 border-t border-border">
                                            <span class="text-content-muted">{{ __('analysis.difference') }}</span>
                                            <span class="font-medium {{ $timeDiff > 0 ? 'text-error' : ($timeDiff < 0 ? 'text-success' : 'text-content-muted') }}">
                                                {{ $timeDiff > 0 ? '+' : '' }}{{ number_format($timeDiff) }} ms
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Code -->
                            <div class="bg-surface-subtle rounded-xl p-4">
                                <h3 class="text-sm font-medium text-content-secondary mb-4">{{ __('analysis.status_code') }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_1') }}</span>
                                        <span class="font-medium text-primary">{{ $comparison['data1']['status']['code'] ?? '--' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-content-muted">{{ __('analysis.analysis_2') }}</span>
                                        <span class="font-medium text-primary">{{ $comparison['data2']['status']['code'] ?? '--' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        @if($availableAnalyses->count() < 2)
            <div class="bg-white rounded-2xl border border-border p-12 text-center">
                <div class="w-16 h-16 bg-surface-subtle rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-primary">{{ __('analysis.not_enough_analyses') }}</h3>
                <p class="mt-2 text-sm text-content-secondary">{{ __('analysis.need_two_analyses') }}</p>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center mt-6 px-5 py-2.5 bg-accent hover:bg-accent-hover text-white text-sm font-medium rounded-xl transition-colors">
                    {{ __('analysis.analyze_more_urls') }}
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-border p-12 text-center">
                <div class="w-16 h-16 bg-surface-subtle rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-primary">{{ __('analysis.select_to_compare') }}</h3>
                <p class="mt-2 text-sm text-content-secondary">{{ __('analysis.choose_two_analyses') }}</p>
            </div>
        @endif
    @endif
</div>
@endsection
