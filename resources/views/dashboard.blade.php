@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ __('dashboard.title') }}</h1>
                <p class="mt-2 text-gray-600">{{ __('dashboard.subtitle') }}</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Analyses -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('dashboard.total_analyses') }}</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $totalAnalyses }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Analyses -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('dashboard.completed') }}</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $completedAnalyses }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Score -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('dashboard.average_score') }}</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ $averageScore ? number_format($averageScore, 1) : '--' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Score Distribution -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500 mb-3">{{ __('dashboard.score_distribution') }}</p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-green-600">{{ __('dashboard.excellent') }}</span>
                                <span>{{ $scoreDistribution['excellent'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-blue-600">{{ __('dashboard.good') }}</span>
                                <span>{{ $scoreDistribution['good'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-yellow-600">{{ __('dashboard.fair') }}</span>
                                <span>{{ $scoreDistribution['fair'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-600">{{ __('dashboard.poor') }}</span>
                                <span>{{ $scoreDistribution['poor'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- URL Analysis Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('dashboard.analyze_new_url') }}</h3>
                            @livewire('url-analysis-form')
                        </div>
                    </div>

                    <!-- Recent Analyses -->
                    <div class="mt-8 bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('dashboard.recent_analyses') }}</h3>
                            @if($recentAnalyses->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.url') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.status') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.score') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.date') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($recentAnalyses as $analysis)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $analysis->url }}">
                                                            {{ $analysis->url }}
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                            @if($analysis->status === 'completed') bg-green-100 text-green-800
                                                            @elseif($analysis->status === 'processing') bg-yellow-100 text-yellow-800
                                                            @elseif($analysis->status === 'failed') bg-red-100 text-red-800
                                                            @else bg-gray-100 text-gray-800
                                                            @endif">
                                                            {{ __('analysis.status_' . $analysis->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        @if($analysis->overall_score)
                                                            <div class="flex items-center">
                                                                <span class="font-medium">{{ number_format($analysis->overall_score, 1) }}</span>
                                                                <div class="ml-2 w-12 h-2 bg-gray-200 rounded-full">
                                                                    <div class="h-2 rounded-full
                                                                        @if($analysis->overall_score >= 90) bg-green-500
                                                                        @elseif($analysis->overall_score >= 70) bg-blue-500
                                                                        @elseif($analysis->overall_score >= 50) bg-yellow-500
                                                                        @else bg-red-500
                                                                        @endif"
                                                                        style="width: {{ $analysis->overall_score }}%">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="text-gray-400">--</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $analysis->created_at->format('M j, Y') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        @if($analysis->status === 'completed')
                                                            <a href="{{ route('analysis.show', $analysis->id) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('ui.view') }}</a>
                                                        @else
                                                            <span class="text-gray-400">--</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('dashboard.no_analyses_yet') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('dashboard.get_started_message') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Recent Activity -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('dashboard.recent_activity') }}</h3>
                            @if($recentActivity->count() > 0)
                                <div class="space-y-3">
                                    @foreach($recentActivity as $activity)
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-2 h-2 rounded-full mt-2
                                                    @if($activity->status === 'completed') bg-green-500
                                                    @elseif($activity->status === 'processing') bg-yellow-500
                                                    @elseif($activity->status === 'failed') bg-red-500
                                                    @else bg-gray-500
                                                    @endif">
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900 truncate">{{ $activity->url }}</p>
                                                <div class="flex items-center justify-between">
                                                    <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                                                    @if($activity->overall_score)
                                                        <span class="text-xs font-medium text-gray-900">{{ number_format($activity->overall_score, 1) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">{{ __('dashboard.no_recent_activity') }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('dashboard.quick_actions') }}</h3>
                            <div class="space-y-3">
                                <a href="{{ route('analysis.history') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                                    {{ __('dashboard.view_all_analyses') }}
                                </a>
                                <a href="{{ route('analysis.compare') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                                    {{ __('dashboard.compare_results') }}
                                </a>
                                <a href="{{ route('user.profile') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                                    {{ __('dashboard.profile_settings') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
