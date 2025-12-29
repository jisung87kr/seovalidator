<div>
    @if(count($analyses) > 0)
        <div class="space-y-3">
            @foreach($analyses as $analysis)
                <a href="{{ route('analysis.show', $analysis['id']) }}"
                   class="block p-4 bg-surface-subtle hover:bg-surface-muted rounded-xl transition-colors cursor-pointer group">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="flex-shrink-0">
                                @if($analysis['status'] === 'completed')
                                    <div class="w-12 h-12 rounded-xl bg-{{ $this->getScoreColor($analysis['overall_score']) }}-100 flex items-center justify-center">
                                        <span class="text-sm font-bold text-{{ $this->getScoreColor($analysis['overall_score']) }}-600">
                                            {{ $analysis['overall_score'] }}
                                        </span>
                                    </div>
                                @elseif($analysis['status'] === 'processing')
                                    <div class="w-12 h-12 rounded-xl bg-accent-light flex items-center justify-center">
                                        <svg class="w-5 h-5 text-accent animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-surface-subtle flex items-center justify-center">
                                        <svg class="w-5 h-5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-primary truncate">
                                    {{ $analysis['url'] }}
                                </p>
                                <p class="text-sm text-content-muted mt-0.5">
                                    {{ $analysis['status'] === 'processing' ? __('analysis.processing') : __('analysis.analyzed') }}
                                    {{ \Carbon\Carbon::parse($analysis['analyzed_at'])->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 flex-shrink-0">
                            @if($analysis['status'] === 'completed')
                                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $this->getScoreColor($analysis['overall_score']) }}-100 text-{{ $this->getScoreColor($analysis['overall_score']) }}-700">
                                    {{ $analysis['overall_score'] }}/100
                                </span>
                            @elseif($analysis['status'] === 'processing')
                                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-accent-light text-accent">
                                    {{ __('analysis.processing') }}
                                </span>
                            @endif

                            <svg class="w-5 h-5 text-content-muted group-hover:text-content transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-surface-subtle rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-medium text-primary">{{ __('dashboard.no_analyses') }}</h3>
            <p class="mt-1 text-sm text-content-secondary">{{ __('dashboard.start_first_analysis') }}</p>
        </div>
    @endif
</div>
