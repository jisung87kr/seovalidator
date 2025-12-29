@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ __('analysis.history_title') }}</h1>
                <p class="mt-2 text-content-secondary">{{ __('analysis.history_subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('analysis.back_to_dashboard') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-accent-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary">{{ __('dashboard.total_analyses') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $totalAnalyses }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-success-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary">{{ __('dashboard.completed') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $completedAnalyses }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-warning-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary">{{ __('dashboard.average_score') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $averageScore ? number_format($averageScore, 1) : '--' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 mb-8">
        <form method="GET" action="{{ route('analysis.history') }}" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-content mb-2">{{ __('analysis.search_urls') }}</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('analysis.enter_url_to_search') }}"
                       class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
            </div>

            <div class="sm:w-40">
                <label for="status" class="block text-sm font-medium text-content mb-2">{{ __('ui.status') }}</label>
                <select id="status"
                        name="status"
                        class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                    <option value="">{{ __('analysis.all_statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('analysis.status_pending') }}</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('analysis.status_processing') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('analysis.status_completed') }}</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('analysis.status_failed') }}</option>
                </select>
            </div>

            <div class="sm:w-40">
                <label for="score_range" class="block text-sm font-medium text-content mb-2">{{ __('analysis.score_range') }}</label>
                <select id="score_range"
                        name="score_range"
                        class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                    <option value="">{{ __('analysis.all_scores') }}</option>
                    <option value="excellent" {{ request('score_range') === 'excellent' ? 'selected' : '' }}>{{ __('dashboard.excellent') }}</option>
                    <option value="good" {{ request('score_range') === 'good' ? 'selected' : '' }}>{{ __('dashboard.good') }}</option>
                    <option value="fair" {{ request('score_range') === 'fair' ? 'selected' : '' }}>{{ __('dashboard.fair') }}</option>
                    <option value="poor" {{ request('score_range') === 'poor' ? 'selected' : '' }}>{{ __('dashboard.poor') }}</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="inline-flex items-center px-5 py-3 bg-accent hover:bg-accent-hover text-white text-sm font-medium rounded-xl transition-colors">
                    {{ __('ui.filter') }}
                </button>
                <a href="{{ route('analysis.history') }}"
                   class="inline-flex items-center px-5 py-3 bg-surface-subtle hover:bg-surface-muted border border-border text-content-secondary text-sm font-medium rounded-xl transition-colors">
                    {{ __('analysis.clear') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="bg-white rounded-2xl border border-border overflow-hidden">
        @if($analyses->count() > 0)
            <!-- Mobile Card View -->
            <div class="block lg:hidden divide-y divide-border">
                @foreach($analyses as $analysis)
                    <a href="{{ $analysis->status === 'completed' ? route('analysis.show', $analysis->id) : '#' }}"
                       class="block p-4 hover:bg-surface-subtle transition-colors {{ $analysis->status !== 'completed' ? 'pointer-events-none' : '' }}">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                @if($analysis->status === 'completed' && $analysis->overall_score)
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center
                                        @if($analysis->overall_score >= 90) bg-success-light
                                        @elseif($analysis->overall_score >= 70) bg-accent-light
                                        @elseif($analysis->overall_score >= 50) bg-warning-light
                                        @else bg-error-light
                                        @endif">
                                        <span class="text-sm font-bold
                                            @if($analysis->overall_score >= 90) text-success-dark
                                            @elseif($analysis->overall_score >= 70) text-accent-dark
                                            @elseif($analysis->overall_score >= 50) text-warning-dark
                                            @else text-error-dark
                                            @endif">
                                            {{ number_format($analysis->overall_score, 0) }}
                                        </span>
                                    </div>
                                @elseif($analysis->status === 'processing')
                                    <div class="w-12 h-12 rounded-xl bg-warning-light flex items-center justify-center">
                                        <svg class="w-5 h-5 text-warning animate-spin" fill="none" viewBox="0 0 24 24">
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
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-primary truncate">{{ $analysis->url }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-medium
                                        @if($analysis->status === 'completed') bg-success-light text-success-dark
                                        @elseif($analysis->status === 'processing') bg-warning-light text-warning-dark
                                        @elseif($analysis->status === 'failed') bg-error-light text-error-dark
                                        @else bg-surface-subtle text-content-muted
                                        @endif">
                                        {{ __('analysis.status_' . $analysis->status) }}
                                    </span>
                                    <span class="text-xs text-content-muted">{{ $analysis->created_at->format('M j, Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-surface-subtle border-b border-border">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('ui.url') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('ui.status') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('analysis.overall_score') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('analysis.technical') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('analysis.content') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('ui.date') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-content-secondary uppercase tracking-wider">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($analyses as $analysis)
                            <tr class="hover:bg-surface-subtle transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-medium text-primary max-w-xs truncate" title="{{ $analysis->url }}">
                                            {{ $analysis->url }}
                                        </div>
                                        @if($analysis->title)
                                            <div class="text-xs text-content-muted max-w-xs truncate mt-0.5" title="{{ $analysis->title }}">
                                                {{ $analysis->title }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium
                                        @if($analysis->status === 'completed') bg-success-light text-success-dark
                                        @elseif($analysis->status === 'processing') bg-warning-light text-warning-dark
                                        @elseif($analysis->status === 'failed') bg-error-light text-error-dark
                                        @else bg-surface-subtle text-content-muted
                                        @endif">
                                        {{ __('analysis.status_' . $analysis->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($analysis->overall_score)
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-primary">{{ number_format($analysis->overall_score, 0) }}</span>
                                            <div class="w-16 h-2 bg-surface-subtle rounded-full overflow-hidden">
                                                <div class="h-full rounded-full
                                                    @if($analysis->overall_score >= 90) bg-success
                                                    @elseif($analysis->overall_score >= 70) bg-accent
                                                    @elseif($analysis->overall_score >= 50) bg-warning
                                                    @else bg-error
                                                    @endif"
                                                    style="width: {{ $analysis->overall_score }}%">
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-content-muted text-sm">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-content">
                                    {{ $analysis->technical_score ? number_format($analysis->technical_score, 0) : '--' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-content">
                                    {{ $analysis->content_score ? number_format($analysis->content_score, 0) : '--' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-content-muted">
                                    <div class="flex flex-col">
                                        <span>{{ $analysis->created_at->format('M j, Y') }}</span>
                                        <span class="text-xs">{{ $analysis->created_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($analysis->status === 'completed')
                                        <div class="flex items-center gap-3">
                                            <a href="{{ route('analysis.show', $analysis->id) }}"
                                               class="text-sm font-medium text-accent hover:text-accent-hover transition-colors">{{ __('ui.view') }}</a>
                                            <a href="{{ route('analysis.compare') }}?analysis1={{ $analysis->id }}"
                                               class="text-sm font-medium text-success hover:text-success-dark transition-colors">{{ __('analysis.compare') }}</a>
                                        </div>
                                    @else
                                        <span class="text-content-muted text-sm">--</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-border">
                {{ $analyses->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-surface-subtle rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-primary">{{ __('analysis.no_analyses_found') }}</h3>
                <p class="mt-2 text-sm text-content-secondary max-w-sm mx-auto">
                    @if(request()->hasAny(['search', 'status', 'score_range']))
                        {{ __('analysis.try_adjusting_filters') }}
                        <a href="{{ route('analysis.history') }}" class="text-accent hover:text-accent-hover font-medium">{{ __('analysis.clear_all_filters') }}</a>
                    @else
                        {{ __('analysis.get_started_by') }}
                        <a href="{{ route('dashboard') }}" class="text-accent hover:text-accent-hover font-medium">{{ __('analysis.analyzing_first_url') }}</a>
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
