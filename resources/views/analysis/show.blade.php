@extends('layouts.app')

@if($analysis->status !== 'completed' && $analysis->status !== 'failed')
@push('scripts')
<script>
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
@endpush
@endif

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ __('analysis.details_title') }}</h1>
                <p class="mt-2 text-sm text-content-secondary font-mono truncate" title="{{ $analysis->url }}">
                    {{ $analysis->url }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-content-muted">
                    <span>{{ __('analysis.analyzed') }}: {{ $analysis->created_at->format('M j, Y \a\t H:i') }}</span>
                    @if($analysis->analyzed_at)
                        <span class="hidden sm:inline">•</span>
                        <span>{{ __('analysis.completed') }}: {{ $analysis->analyzed_at->format('M j, Y \a\t H:i') }}</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('analysis.compare') }}?analysis1={{ $analysis->id }}"
                   class="inline-flex items-center px-5 py-2.5 bg-accent hover:bg-accent-hover text-white text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    {{ __('analysis.compare') }}
                </a>
                <a href="{{ route('analysis.history') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-white border border-border hover:bg-surface-subtle text-content-secondary text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    {{ __('analysis.back_to_history') }}
                </a>
            </div>
        </div>
    </div>

    @include('analysis.partials.results')
</div>
@endsection
