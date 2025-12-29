{{-- Analysis Results Partial - Shared between authenticated and guest views --}}

@php
    function getScoreColorClass($score) {
        if ($score >= 90) return 'success';
        if ($score >= 70) return 'accent';
        if ($score >= 50) return 'warning';
        return 'error';
    }
@endphp

@if($analysis->status !== 'completed')
    <!-- Status Alert -->
    <div class="bg-white rounded-2xl border border-border p-6 sm:p-8">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                @if($analysis->status === 'processing')
                    <div class="w-12 h-12 rounded-xl bg-warning-light flex items-center justify-center">
                        <svg class="w-6 h-6 text-warning animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                @elseif($analysis->status === 'failed')
                    <div class="w-12 h-12 rounded-xl bg-error-light flex items-center justify-center">
                        <svg class="w-6 h-6 text-error" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-xl bg-surface-subtle flex items-center justify-center">
                        <svg class="w-6 h-6 text-content-muted" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-primary">
                    {{ __('analysis.analysis_status', ['status' => __('analysis.status_' . $analysis->status)]) }}
                </h3>
                <p class="mt-2 text-content-secondary">
                    @if($analysis->status === 'processing')
                        {{ __('analysis.processing_message') }}
                    @elseif($analysis->status === 'failed')
                        {{ __('analysis.failed_message') }}
                        @if($analysis->error_message)
                            <br>{{ __('analysis.error_prefix') }}: {{ $analysis->error_message }}
                        @endif
                    @else
                        {{ __('analysis.pending_message') }}
                    @endif
                </p>
                @if($analysis->status === 'processing')
                    <div class="mt-4 flex items-center gap-2">
                        <div class="flex gap-1">
                            <div class="w-2 h-2 bg-warning rounded-full animate-pulse"></div>
                            <div class="w-2 h-2 bg-warning rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-warning rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                        </div>
                        <span class="text-sm text-warning">{{ __('dashboard.processing') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    <!-- Analysis Results -->
    <div class="space-y-6">
        <!-- Score Overview -->
        <div class="bg-white rounded-2xl border border-border overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-border">
                <h2 class="text-lg font-semibold text-primary">{{ __('analysis.overall_seo_score') }}</h2>
            </div>
            <div class="p-6 sm:p-8">
                <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                    <!-- Main Score Circle -->
                    <div class="flex justify-center lg:justify-start">
                        <div class="relative w-40 h-40">
                            @php $scoreColor = getScoreColorClass($analysis->overall_score); @endphp
                            <svg class="w-40 h-40 transform -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" class="text-surface-subtle"></circle>
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    class="text-{{ $scoreColor }}"
                                    style="stroke-dasharray: {{ 2 * pi() * 50 }}; stroke-dashoffset: {{ 2 * pi() * 50 * (1 - $analysis->overall_score / 100) }}"
                                ></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-bold text-primary">{{ number_format($analysis->overall_score, 0) }}</span>
                                <span class="text-sm text-content-muted">/100</span>
                            </div>
                        </div>
                    </div>

                    <!-- Score Label & Breakdown -->
                    <div class="flex-1">
                        <div class="mb-6 text-center lg:text-left">
                            <span class="inline-flex px-4 py-2 rounded-xl text-sm font-semibold bg-{{ $scoreColor }}-light text-{{ $scoreColor }}-dark">
                                @if($analysis->overall_score >= 90) {{ __('analysis.excellent_label') }}
                                @elseif($analysis->overall_score >= 70) {{ __('analysis.good_label') }}
                                @elseif($analysis->overall_score >= 50) {{ __('analysis.fair_label') }}
                                @else {{ __('analysis.poor_label') }}
                                @endif
                            </span>
                        </div>

                        <!-- Score Breakdown -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-surface-subtle rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-content">{{ __('analysis.technical_seo') }}</span>
                                    <span class="text-sm font-bold text-primary">{{ $analysis->technical_score ? number_format($analysis->technical_score, 0) : '--' }}</span>
                                </div>
                                <div class="h-2 bg-white rounded-full overflow-hidden">
                                    @if($analysis->technical_score)
                                        <div class="h-full bg-accent rounded-full transition-all duration-500" style="width: {{ $analysis->technical_score }}%"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-surface-subtle rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-content">{{ __('analysis.content_quality') }}</span>
                                    <span class="text-sm font-bold text-primary">{{ $analysis->content_score ? number_format($analysis->content_score, 0) : '--' }}</span>
                                </div>
                                <div class="h-2 bg-white rounded-full overflow-hidden">
                                    @if($analysis->content_score)
                                        <div class="h-full bg-success rounded-full transition-all duration-500" style="width: {{ $analysis->content_score }}%"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-surface-subtle rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-content">{{ __('analysis.performance') }}</span>
                                    <span class="text-sm font-bold text-primary">{{ $analysis->performance_score ? number_format($analysis->performance_score, 0) : '--' }}</span>
                                </div>
                                <div class="h-2 bg-white rounded-full overflow-hidden">
                                    @if($analysis->performance_score)
                                        <div class="h-full bg-warning rounded-full transition-all duration-500" style="width: {{ $analysis->performance_score }}%"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-surface-subtle rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-content">{{ __('analysis.accessibility') }}</span>
                                    <span class="text-sm font-bold text-primary">{{ $analysis->accessibility_score ? number_format($analysis->accessibility_score, 0) : '--' }}</span>
                                </div>
                                <div class="h-2 bg-white rounded-full overflow-hidden">
                                    @if($analysis->accessibility_score)
                                        <div class="h-full bg-purple-500 rounded-full transition-all duration-500" style="width: {{ $analysis->accessibility_score }}%"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($analysisData))
            <!-- Page Information -->
            @if(isset($analysisData['seo_elements']['meta']))
                <div class="bg-white rounded-2xl border border-border overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-border">
                        <h2 class="text-lg font-semibold text-primary">{{ __('analysis.page_information') }}</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div>
                                <h3 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.page_title') }}</h3>
                                <div class="bg-surface-subtle rounded-xl p-4">
                                    <p class="text-sm text-primary break-words">
                                        {{ $analysisData['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                                    </p>
                                    @if(isset($analysisData['seo_elements']['meta']['title_length']))
                                        <p class="text-xs text-content-muted mt-2">{{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['title_length'] }} {{ __('analysis.characters') }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <h3 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.meta_description') }}</h3>
                                <div class="bg-surface-subtle rounded-xl p-4">
                                    <p class="text-sm text-primary break-words">
                                        {{ $analysisData['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                                    </p>
                                    @if(isset($analysisData['seo_elements']['meta']['description_length']))
                                        <p class="text-xs text-content-muted mt-2">{{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['description_length'] }} {{ __('analysis.characters') }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Keywords -->
                            <div>
                                <h3 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.meta_keywords') }}</h3>
                                <div class="bg-surface-subtle rounded-xl p-4">
                                    <p class="text-sm text-primary break-words">
                                        {{ $analysisData['seo_elements']['meta']['keywords'] ?? __('analysis.no_keywords_found') }}
                                    </p>
                                    @if(isset($analysisData['seo_elements']['meta']['keywords_count']))
                                        <p class="text-xs text-content-muted mt-2">{{ __('analysis.count') }}: {{ $analysisData['seo_elements']['meta']['keywords_count'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- OG Image -->
                            <div>
                                <h3 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.og_image') }}</h3>
                                @if(isset($analysisData['seo_elements']['social_media']['open_graph']) && $analysisData['seo_elements']['social_media']['open_graph']['image'])
                                    <div class="bg-surface-subtle rounded-xl p-4">
                                        <img src="{{ $analysisData['seo_elements']['social_media']['open_graph']['image'] }}"
                                             alt="Open Graph Image"
                                             class="w-full h-auto rounded-lg mb-2"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <p class="text-sm text-primary break-all hidden">
                                            {{ $analysisData['seo_elements']['social_media']['open_graph']['image'] }}
                                        </p>
                                        <p class="text-xs text-content-muted mt-2">{{ Str::limit($analysisData['seo_elements']['social_media']['open_graph']['image'], 60) }}</p>
                                    </div>
                                @else
                                    <div class="bg-surface-subtle rounded-xl p-4">
                                        <p class="text-sm text-content-muted">{{ __('analysis.no_og_image_found') }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Robot Crawlability -->
                            <div class="md:col-span-2">
                                <h3 class="text-sm font-medium text-content-secondary mb-2">{{ __('analysis.robot_crawlability') }}</h3>
                                <div class="bg-surface-subtle rounded-xl p-4">
                                    @if(isset($analysisData['seo_elements']['robots']))
                                        @if($analysisData['seo_elements']['robots']['is_allowed'])
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-success-light flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-medium text-success-dark">{{ __('analysis.crawlable') }}</span>
                                                    @if(isset($analysisData['seo_elements']['robots']['meta_robots']))
                                                        <p class="text-xs text-content-muted mt-0.5">Meta Robots: {{ $analysisData['seo_elements']['robots']['meta_robots'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-error-light flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-error" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-medium text-error-dark">{{ __('analysis.not_crawlable') }}</span>
                                                    @if(isset($analysisData['seo_elements']['robots']['disallow_reason']))
                                                        <p class="text-xs text-content-muted mt-0.5">{{ __('analysis.reason') }}: {{ $analysisData['seo_elements']['robots']['disallow_reason'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-sm text-content-muted">{{ __('analysis.robots_info_unavailable') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Issues and Recommendations -->
            @if(isset($analysisData['scores']['category_scores']))
                <div class="bg-white rounded-2xl border border-border overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-border">
                        <h2 class="text-lg font-semibold text-primary">{{ __('analysis.issues_and_recommendations') }}</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="space-y-8">
                            @foreach($analysisData['scores']['category_scores'] as $category => $categoryData)
                                @if(isset($categoryData['issues']) && count($categoryData['issues']) > 0)
                                    <div>
                                        <h3 class="text-base font-semibold text-primary mb-4 capitalize">{{ str_replace('_', ' ', $category) }}</h3>

                                        <!-- Issues -->
                                        <div class="space-y-3 mb-4">
                                            @foreach($categoryData['issues'] as $issue)
                                                <div class="flex items-start gap-3 p-4 bg-error-light rounded-xl">
                                                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-error/10 flex items-center justify-center mt-0.5">
                                                        <svg class="h-4 w-4 text-error" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                    <p class="text-sm text-error-dark break-words">{{ $issue }}</p>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Recommendations -->
                                        @if(isset($categoryData['recommendations']) && count($categoryData['recommendations']) > 0)
                                            <div class="space-y-3">
                                                @foreach($categoryData['recommendations'] as $recommendation)
                                                    <div class="flex items-start gap-3 p-4 bg-accent-light rounded-xl">
                                                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-accent/10 flex items-center justify-center mt-0.5">
                                                            <svg class="h-4 w-4 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                        <p class="text-sm text-accent-dark break-words">{{ $recommendation }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Technical Details -->
            @if(isset($analysisData['crawl_data']))
                <div class="bg-white rounded-2xl border border-border overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-border">
                        <h2 class="text-lg font-semibold text-primary">{{ __('analysis.technical_details') }}</h2>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6">
                            <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.page_size') }}</h3>
                                <p class="text-xl sm:text-2xl font-bold text-primary">
                                    {{ isset($analysisData['crawl_data']['html_size']) ? number_format($analysisData['crawl_data']['html_size'] / 1024, 1) : '--' }}
                                    <span class="text-sm font-normal text-content-muted">KB</span>
                                </p>
                            </div>

                            <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.load_time') }}</h3>
                                <p class="text-xl sm:text-2xl font-bold text-primary">
                                    {{ isset($analysisData['crawl_data']['load_time_ms']) ? number_format($analysisData['crawl_data']['load_time_ms']) : '--' }}
                                    <span class="text-sm font-normal text-content-muted">ms</span>
                                </p>
                            </div>

                            <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.status_code') }}</h3>
                                <p class="text-xl sm:text-2xl font-bold text-primary">
                                    {{ $analysisData['status']['code'] ?? '--' }}
                                </p>
                            </div>
                        </div>

                        @if(isset($analysisData['seo_elements']['images']))
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6 mt-4 sm:mt-6">
                                <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                    <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.total_images') }}</h3>
                                    <p class="text-xl sm:text-2xl font-bold text-primary">
                                        {{ $analysisData['seo_elements']['images']['total_count'] ?? 0 }}
                                    </p>
                                </div>

                                <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                    <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.images_missing_alt') }}</h3>
                                    <p class="text-xl sm:text-2xl font-bold text-{{ ($analysisData['seo_elements']['images']['without_alt_count'] ?? 0) > 0 ? 'error' : 'success' }}">
                                        {{ $analysisData['seo_elements']['images']['without_alt_count'] ?? 0 }}
                                    </p>
                                </div>

                                <div class="bg-surface-subtle rounded-xl p-4 sm:p-5">
                                    <h3 class="text-sm font-medium text-content-secondary mb-1">{{ __('analysis.total_links') }}</h3>
                                    <p class="text-xl sm:text-2xl font-bold text-primary">
                                        {{ $analysisData['seo_elements']['links']['total_count'] ?? 0 }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
@endif
