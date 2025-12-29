<div>
    @if($analysis && $analysis['status'] === 'completed')
        <div class="bg-white rounded-2xl border border-border overflow-hidden">
            <!-- Header -->
            <div class="p-6 sm:p-8 border-b border-border">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-primary">{{ __('analysis.results_title') }}</h3>
                        <p class="text-sm text-content-secondary mt-1 font-mono">{{ $analysis['url'] }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1.5 rounded-lg text-sm font-medium bg-{{ $this->getScoreColor($analysis['overall_score']) }}-100 text-{{ $this->getScoreColor($analysis['overall_score']) }}-700">
                            {{ $this->getScoreGrade($analysis['overall_score']) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Score Section -->
            <div class="p-6 sm:p-8">
                <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                    <!-- Overall Score Circle -->
                    <div class="flex justify-center lg:justify-start">
                        <div class="relative w-36 h-36">
                            <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="10" class="text-surface-subtle"></circle>
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    class="text-{{ $this->getScoreColor($analysis['overall_score']) }}-500"
                                    style="stroke-dasharray: {{ 2 * pi() * 50 }}; stroke-dashoffset: {{ 2 * pi() * 50 * (1 - $analysis['overall_score'] / 100) }}"
                                ></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-bold text-primary">{{ $analysis['overall_score'] }}</span>
                                <span class="text-sm text-content-muted">/100</span>
                            </div>
                        </div>
                    </div>

                    <!-- Score Breakdown -->
                    <div class="flex-1 grid grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-content">{{ __('analysis.technical_seo') }}</span>
                                <span class="text-sm font-bold text-{{ $this->getScoreColor($analysis['technical_score']) }}-600">{{ $analysis['technical_score'] }}</span>
                            </div>
                            <div class="h-2 bg-surface-subtle rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $this->getScoreColor($analysis['technical_score']) }}-500 rounded-full transition-all duration-500" style="width: {{ $analysis['technical_score'] }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-content">{{ __('analysis.content') }}</span>
                                <span class="text-sm font-bold text-{{ $this->getScoreColor($analysis['content_score']) }}-600">{{ $analysis['content_score'] }}</span>
                            </div>
                            <div class="h-2 bg-surface-subtle rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $this->getScoreColor($analysis['content_score']) }}-500 rounded-full transition-all duration-500" style="width: {{ $analysis['content_score'] }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-content">{{ __('analysis.performance') }}</span>
                                <span class="text-sm font-bold text-{{ $this->getScoreColor($analysis['performance_score']) }}-600">{{ $analysis['performance_score'] }}</span>
                            </div>
                            <div class="h-2 bg-surface-subtle rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $this->getScoreColor($analysis['performance_score']) }}-500 rounded-full transition-all duration-500" style="width: {{ $analysis['performance_score'] }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-content">{{ __('analysis.accessibility') }}</span>
                                <span class="text-sm font-bold text-{{ $this->getScoreColor($analysis['accessibility_score']) }}-600">{{ $analysis['accessibility_score'] }}</span>
                            </div>
                            <div class="h-2 bg-surface-subtle rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $this->getScoreColor($analysis['accessibility_score']) }}-500 rounded-full transition-all duration-500" style="width: {{ $analysis['accessibility_score'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toggle Details Button -->
                <div class="flex justify-center mt-8">
                    <button
                        wire:click="toggleDetails"
                        class="inline-flex items-center px-5 py-2.5 bg-surface-subtle hover:bg-surface-muted text-sm font-medium text-content-secondary rounded-xl transition-colors"
                    >
                        {{ $showDetails ? __('analysis.hide_details') : __('analysis.show_details') }}
                        <svg class="ml-2 w-4 h-4 transition-transform {{ $showDetails ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Detailed Breakdown -->
                @if($showDetails)
                    <div class="mt-8 pt-8 border-t border-border">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Technical SEO Details -->
                            <div class="bg-surface-subtle rounded-xl p-5">
                                <h4 class="text-base font-semibold text-primary mb-4">{{ __('analysis.technical_seo') }}</h4>
                                <div class="space-y-3">
                                    @foreach($analysis['analysis_data']['technical'] as $item => $data)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-content-secondary capitalize">{{ str_replace('_', ' ', $item) }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-{{ $this->getScoreColor($data['score']) }}-600">{{ $data['score'] }}</span>
                                                @if($data['issues'] > 0)
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-error-light text-error-dark">
                                                        {{ $data['issues'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Content Details -->
                            <div class="bg-surface-subtle rounded-xl p-5">
                                <h4 class="text-base font-semibold text-primary mb-4">{{ __('analysis.content') }}</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.word_count') }}</span>
                                        <span class="text-sm font-medium text-content">{{ $analysis['analysis_data']['content']['word_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.readability') }}</span>
                                        <span class="text-sm font-medium text-{{ $this->getScoreColor($analysis['analysis_data']['content']['readability']) }}-600">
                                            {{ $analysis['analysis_data']['content']['readability'] }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.keyword_density') }}</span>
                                        <span class="text-sm font-medium text-content">{{ $analysis['analysis_data']['content']['keyword_density'] }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Details -->
                            <div class="bg-surface-subtle rounded-xl p-5">
                                <h4 class="text-base font-semibold text-primary mb-4">{{ __('analysis.performance') }}</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.page_size') }}</span>
                                        <span class="text-sm font-medium text-content">{{ $analysis['analysis_data']['performance']['page_size'] }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.load_time') }}</span>
                                        <span class="text-sm font-medium text-content">{{ $analysis['analysis_data']['performance']['load_time'] }}s</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-content-secondary">{{ __('analysis.http_requests') }}</span>
                                        <span class="text-sm font-medium text-content">{{ $analysis['analysis_data']['performance']['requests'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Accessibility Details -->
                            <div class="bg-surface-subtle rounded-xl p-5">
                                <h4 class="text-base font-semibold text-primary mb-4">{{ __('analysis.accessibility') }}</h4>
                                <div class="space-y-3">
                                    @foreach($analysis['analysis_data']['accessibility'] as $item => $score)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-content-secondary capitalize">{{ str_replace('_', ' ', $item) }}</span>
                                            <span class="text-sm font-medium text-{{ $this->getScoreColor($score) }}-600">{{ $score }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="px-6 sm:px-8 py-5 bg-surface-subtle border-t border-border">
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('analysis.show', $analysis['id']) }}" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        {{ __('analysis.view_full_report') }}
                    </a>
                    <button class="inline-flex items-center px-5 py-2.5 bg-white border border-border hover:bg-surface-subtle text-sm font-medium text-content-secondary rounded-xl transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('analysis.re_analyze') }}
                    </button>
                </div>
            </div>
        </div>

    @elseif($analysis && $analysis['status'] === 'processing')
        <!-- Processing State -->
        <div class="bg-white rounded-2xl border border-border p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-6">
                <svg class="w-16 h-16 text-accent animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-primary">{{ __('analysis.analyzing') }}</h3>
            <p class="text-content-secondary mt-2 font-mono text-sm">{{ $analysis['url'] }}</p>
            <p class="text-content-muted text-sm mt-4">{{ __('analysis.please_wait') }}</p>
        </div>
    @else
        <!-- No Analysis State -->
        <div class="bg-white rounded-2xl border border-border p-12 text-center">
            <div class="w-16 h-16 bg-surface-subtle rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-primary">{{ __('analysis.no_analysis') }}</h3>
            <p class="mt-2 text-sm text-content-secondary">{{ __('analysis.start_analyzing') }}</p>
        </div>
    @endif
</div>
