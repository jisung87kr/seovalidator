@extends('layouts.app')

@section('title', __('analysis.analysis_results'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    {{ __('ui.dashboard') }}
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">{{ __('analysis.analysis_number', ['id' => $id]) }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Analysis Results -->
    <div class="mb-8">
        @livewire('seo-score-display')
    </div>

    <!-- Detailed Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Score Trends Chart -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('analysis.score_breakdown') }}</h3>
            <div class="h-64" id="scoreChart">
                <canvas id="scoreBreakdownChart"></canvas>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('analysis.performance_metrics') }}</h3>
            <div class="h-64" id="performanceChart">
                <canvas id="performanceMetricsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recommendations Section -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('analysis.recommendations') }}</h3>
        <div class="space-y-4">
            <div class="border-l-4 border-red-500 bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 14.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800">{{ __('analysis.critical_issues') }}</h4>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>{{ __('analysis.missing_meta_description') }}</li>
                                <li>{{ __('analysis.large_page_size') }}</li>
                                <li>{{ __('analysis.images_without_alt') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">{{ __('analysis.warnings') }}</h4>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>{{ __('analysis.h1_not_descriptive') }}</li>
                                <li>{{ __('analysis.optimize_image_compression') }}</li>
                                <li>{{ __('analysis.internal_links_lack_text') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-l-4 border-green-500 bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-green-800">{{ __('analysis.good_practices') }}</h4>
                        <div class="mt-2 text-sm text-green-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>{{ __('analysis.title_tag_optimized') }}</li>
                                <li>{{ __('analysis.good_heading_structure') }}</li>
                                <li>{{ __('analysis.mobile_responsive') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Score Breakdown Chart
    const scoreCtx = document.getElementById('scoreBreakdownChart');
    if (scoreCtx) {
        new Chart(scoreCtx, {
            type: 'radar',
            data: {
                labels: [
                    '{{ __('analysis.technical_seo') }}',
                    '{{ __('analysis.content') }}',
                    '{{ __('analysis.performance') }}',
                    '{{ __('analysis.accessibility') }}'
                ],
                datasets: [{
                    label: '{{ __('analysis.current_scores') }}',
                    data: [85, 72, 68, 83],
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Performance Metrics Chart
    const perfCtx = document.getElementById('performanceMetricsChart');
    if (perfCtx) {
        new Chart(perfCtx, {
            type: 'bar',
            data: {
                labels: [
                    '{{ __('analysis.load_time') }}',
                    '{{ __('analysis.page_size') }}',
                    '{{ __('analysis.http_requests') }}'
                ],
                datasets: [{
                    label: '{{ __('analysis.performance_metrics_label') }}',
                    data: [3.2, 2.4, 45],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(59, 130, 246, 0.5)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endsection