@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Dashboard Header -->
    <div class="mb-10">
        <h1 class="text-2xl sm:text-3xl font-bold text-primary">Dashboard</h1>
        <p class="mt-2 text-content-secondary">{{ __('dashboard.subtitle') }}</p>
    </div>

    <!-- URL Analysis Form -->
    <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 mb-8">
        @livewire('url-analysis-form')
    </div>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-accent-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary truncate">{{ __('dashboard.total_analyses') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $totalAnalyses ?? 0 }}</p>
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
                    <p class="text-sm text-content-secondary truncate">{{ __('dashboard.avg_score') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $avgScore ?? '--' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-warning-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary truncate">{{ __('dashboard.this_month') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $thisMonthCount ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-border p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-error-light rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 14.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-content-secondary truncate">{{ __('dashboard.issues_found') }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $issuesCount ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Analysis Results -->
    <div class="mb-10">
        @livewire('seo-score-display')
    </div>

    <!-- Recent Analyses -->
    <div class="bg-white rounded-2xl border border-border overflow-hidden">
        <div class="px-6 sm:px-8 py-5 border-b border-border">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-primary">{{ __('dashboard.recent_analyses') }}</h2>
                <a href="{{ route('analysis.history') }}" class="text-sm text-accent hover:text-accent-hover font-medium transition-colors">
                    {{ __('dashboard.view_all') }}
                </a>
            </div>
        </div>
        <div class="p-6 sm:p-8">
            @livewire('recent-analyses-list')
        </div>
    </div>
</div>
@endsection
