@extends('layouts.app')

@section('content')
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 sm:mb-8">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start space-y-4 lg:space-y-0">
                    <div>
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">{{ __('analysis.details_title') }}</h1>
                        <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-2xl break-all sm:truncate" title="{{ $analysis->url }}">{{ $analysis->url }}</p>
                        <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-1 sm:space-y-0 text-xs sm:text-sm text-gray-500">
                            <span>{{ __('analysis.analyzed') }}: {{ $analysis->created_at->format('M j, Y \a\t H:i') }}</span>
                            @if($analysis->analyzed_at)
                                <span>•</span>
                                <span>{{ __('analysis.completed') }}: {{ $analysis->analyzed_at->format('M j, Y \a\t H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 lg:mt-0">
                        @if($analysis->status === 'completed')
                            <a href="{{ route('analysis.export-pdf', $analysis->id) }}"
                               class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="hidden sm:inline">{{ __('analysis.export_pdf') }}</span>
                                <span class="sm:hidden ml-1">PDF</span>
                            </a>
                        @endif
                        <a href="{{ route('analysis.compare') }}?analysis1={{ $analysis->id }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            <span class="hidden sm:inline">{{ __('analysis.compare') }}</span>
                            <span class="sm:hidden">비교</span>
                        </a>
                        <a href="{{ route('analysis.history') }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            <span class="hidden sm:inline">{{ __('analysis.back_to_history') }}</span>
                            <span class="sm:hidden">목록</span>
                        </a>
                    </div>
                </div>
            </div>

            @if($analysis->status !== 'completed')
                <!-- Status Alert -->
                <div class="mb-8 rounded-md p-4
                    @if($analysis->status === 'processing') bg-yellow-50 border border-yellow-200
                    @elseif($analysis->status === 'failed') bg-red-50 border border-red-200
                    @else bg-gray-50 border border-gray-200
                    @endif">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            @if($analysis->status === 'processing')
                                <svg class="h-5 w-5 text-yellow-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            @elseif($analysis->status === 'failed')
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium
                                @if($analysis->status === 'processing') text-yellow-800
                                @elseif($analysis->status === 'failed') text-red-800
                                @else text-gray-800
                                @endif">
                                {{ __('analysis.analysis_status', ['status' => __('analysis.status_' . $analysis->status)]) }}
                            </h3>
                            <div class="mt-2 text-sm
                                @if($analysis->status === 'processing') text-yellow-700
                                @elseif($analysis->status === 'failed') text-red-700
                                @else text-gray-700
                                @endif">
                                @if($analysis->status === 'processing')
                                    {{ __('analysis.processing_message') }}
                                @elseif($analysis->status === 'failed')
                                    {{ __('analysis.failed_message') }}
                                    @if($analysis->error_message)
                                        {{ __('analysis.error_prefix') }}: {{ $analysis->error_message }}
                                    @endif
                                @else
                                    {{ __('analysis.pending_message') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Analysis Results -->
                <div class="space-y-8">
                    <!-- Score Overview -->
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-6">{{ __('analysis.overall_seo_score') }}</h2>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Main Score -->
                                <div class="flex items-center justify-center">
                                    <div class="relative">
                                        <div class="w-32 h-32 rounded-full border-8 border-gray-200 flex items-center justify-center
                                            @if($analysis->overall_score >= 90) border-green-500
                                            @elseif($analysis->overall_score >= 70) border-blue-500
                                            @elseif($analysis->overall_score >= 50) border-yellow-500
                                            @else border-red-500
                                            @endif">
                                            <div class="text-center">
                                                <div class="text-3xl font-bold text-gray-900">{{ number_format($analysis->overall_score, 1) }}</div>
                                                <div class="text-sm text-gray-500">/ 100</div>
                                            </div>
                                        </div>
                                        <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-nowrap">
                                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                                @if($analysis->overall_score >= 90) bg-green-100 text-green-800
                                                @elseif($analysis->overall_score >= 70) bg-blue-100 text-blue-800
                                                @elseif($analysis->overall_score >= 50) bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                @if($analysis->overall_score >= 90) {{ __('analysis.excellent_label') }}
                                                @elseif($analysis->overall_score >= 70) {{ __('analysis.good_label') }}
                                                @elseif($analysis->overall_score >= 50) {{ __('analysis.fair_label') }}
                                                @else {{ __('analysis.poor_label') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Score Breakdown -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">{{ __('analysis.technical_seo') }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium">{{ $analysis->technical_score ? number_format($analysis->technical_score, 1) : '--' }}</span>
                                            <div class="w-16 sm:w-20 h-2 bg-gray-200 rounded-full">
                                                @if($analysis->technical_score)
                                                    <div class="h-2 rounded-full bg-blue-500" style="width: {{ $analysis->technical_score }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">{{ __('analysis.content_quality') }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium">{{ $analysis->content_score ? number_format($analysis->content_score, 1) : '--' }}</span>
                                            <div class="w-16 sm:w-20 h-2 bg-gray-200 rounded-full">
                                                @if($analysis->content_score)
                                                    <div class="h-2 rounded-full bg-green-500" style="width: {{ $analysis->content_score }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">{{ __('analysis.performance') }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium">{{ $analysis->performance_score ? number_format($analysis->performance_score, 1) : '--' }}</span>
                                            <div class="w-16 sm:w-20 h-2 bg-gray-200 rounded-full">
                                                @if($analysis->performance_score)
                                                    <div class="h-2 rounded-full bg-yellow-500" style="width: {{ $analysis->performance_score }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">{{ __('analysis.accessibility') }}</span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium">{{ $analysis->accessibility_score ? number_format($analysis->accessibility_score, 1) : '--' }}</span>
                                            <div class="w-16 sm:w-20 h-2 bg-gray-200 rounded-full">
                                                @if($analysis->accessibility_score)
                                                    <div class="h-2 rounded-full bg-purple-500" style="width: {{ $analysis->accessibility_score }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($analysisData))
                        <!-- Key Information -->
                        @if(isset($analysisData['seo_elements']['meta']))
                            <div class="bg-white shadow-sm rounded-lg">
                                <div class="p-4 sm:p-6">
                                    <h2 class="text-base sm:text-lg font-medium text-gray-900 mb-4 sm:mb-6">{{ __('analysis.page_information') }}</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">{{ __('analysis.page_title') }}</h3>
                                            <p class="text-xs sm:text-sm text-gray-900 bg-gray-50 p-2 sm:p-3 rounded break-words">
                                                {{ $analysisData['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                                            </p>
                                            @if(isset($analysisData['seo_elements']['meta']['title_length']))
                                                <p class="text-xs text-gray-500 mt-1">{{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['title_length'] }} {{ __('analysis.characters') }}</p>
                                            @endif
                                        </div>

                                        <div>
                                            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">{{ __('analysis.meta_description') }}</h3>
                                            <p class="text-xs sm:text-sm text-gray-900 bg-gray-50 p-2 sm:p-3 rounded break-words">
                                                {{ $analysisData['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                                            </p>
                                            @if(isset($analysisData['seo_elements']['meta']['description_length']))
                                                <p class="text-xs text-gray-500 mt-1">{{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['description_length'] }} {{ __('analysis.characters') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Issues and Recommendations -->
                        @if(isset($analysisData['scores']['category_scores']))
                            <div class="bg-white shadow-sm rounded-lg">
                                <div class="p-4 sm:p-6">
                                    <h2 class="text-base sm:text-lg font-medium text-gray-900 mb-4 sm:mb-6">{{ __('analysis.issues_and_recommendations') }}</h2>
                                    <div class="space-y-4 sm:space-y-6">
                                        @foreach($analysisData['scores']['category_scores'] as $category => $categoryData)
                                            @if(isset($categoryData['issues']) && count($categoryData['issues']) > 0)
                                                <div>
                                                    <h3 class="text-sm sm:text-base font-medium text-gray-800 mb-2 sm:mb-3 capitalize">{{ str_replace('_', ' ', $category) }}</h3>
                                                    <div class="space-y-2">
                                                        @foreach($categoryData['issues'] as $issue)
                                                            <div class="flex items-start space-x-2 sm:space-x-3 p-2 sm:p-3 bg-red-50 rounded-lg">
                                                                <div class="flex-shrink-0 mt-0.5">
                                                                    <svg class="h-3 w-3 sm:h-4 sm:w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs sm:text-sm text-red-800 break-words">{{ $issue }}</p>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    @if(isset($categoryData['recommendations']) && count($categoryData['recommendations']) > 0)
                                                        <div class="mt-3 sm:mt-4 space-y-2">
                                                            @foreach($categoryData['recommendations'] as $recommendation)
                                                                <div class="flex items-start space-x-2 sm:space-x-3 p-2 sm:p-3 bg-blue-50 rounded-lg">
                                                                    <div class="flex-shrink-0 mt-0.5">
                                                                        <svg class="h-3 w-3 sm:h-4 sm:w-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-xs sm:text-sm text-blue-800 break-words">{{ $recommendation }}</p>
                                                                    </div>
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
                            <div class="bg-white shadow-sm rounded-lg">
                                <div class="p-4 sm:p-6">
                                    <h2 class="text-base sm:text-lg font-medium text-gray-900 mb-4 sm:mb-6">{{ __('analysis.technical_details') }}</h2>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.page_size') }}</h3>
                                            <p class="text-base sm:text-lg font-semibold text-gray-900">
                                                {{ isset($analysisData['crawl_data']['html_size']) ? number_format($analysisData['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}
                                            </p>
                                        </div>

                                        <div>
                                            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.load_time') }}</h3>
                                            <p class="text-base sm:text-lg font-semibold text-gray-900">
                                                {{ isset($analysisData['crawl_data']['load_time_ms']) ? number_format($analysisData['crawl_data']['load_time_ms']) . ' ms' : '--' }}
                                            </p>
                                        </div>

                                        <div>
                                            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.status_code') }}</h3>
                                            <p class="text-base sm:text-lg font-semibold text-gray-900">
                                                {{ $analysisData['status']['code'] ?? '--' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if(isset($analysisData['seo_elements']['images']))
                                        <div class="mt-4 sm:mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                                            <div>
                                                <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.total_images') }}</h3>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900">
                                                    {{ $analysisData['seo_elements']['images']['total_count'] ?? 0 }}
                                                </p>
                                            </div>

                                            <div>
                                                <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.images_missing_alt') }}</h3>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900">
                                                    {{ $analysisData['seo_elements']['images']['without_alt_count'] ?? 0 }}
                                                </p>
                                            </div>

                                            <div>
                                                <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">{{ __('analysis.total_links') }}</h3>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900">
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
        </div>
    </div>
@endsection
