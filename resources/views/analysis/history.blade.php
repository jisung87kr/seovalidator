@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ __('analysis.history_title') }}</h1>
                        <p class="mt-2 text-gray-600">{{ __('analysis.history_subtitle') }}</p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        {{ __('analysis.back_to_dashboard') }}
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <form method="GET" action="{{ route('analysis.history') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">{{ __('analysis.search_urls') }}</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('analysis.enter_url_to_search') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">{{ __('ui.status') }}</label>
                            <select id="status"
                                    name="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('analysis.all_statuses') }}</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('analysis.status_pending') }}</option>
                                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('analysis.status_processing') }}</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('analysis.status_completed') }}</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('analysis.status_failed') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="score_range" class="block text-sm font-medium text-gray-700">{{ __('analysis.score_range') }}</label>
                            <select id="score_range"
                                    name="score_range"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('analysis.all_scores') }}</option>
                                <option value="excellent" {{ request('score_range') === 'excellent' ? 'selected' : '' }}>{{ __('dashboard.excellent') }}</option>
                                <option value="good" {{ request('score_range') === 'good' ? 'selected' : '' }}>{{ __('dashboard.good') }}</option>
                                <option value="fair" {{ request('score_range') === 'fair' ? 'selected' : '' }}>{{ __('dashboard.fair') }}</option>
                                <option value="poor" {{ request('score_range') === 'poor' ? 'selected' : '' }}>{{ __('dashboard.poor') }}</option>
                            </select>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ __('ui.filter') }}
                            </button>
                            <a href="{{ route('analysis.history') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                {{ __('analysis.clear') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-6">
                    @if($analyses->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.url') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.status') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('analysis.overall_score') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('analysis.technical') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('analysis.content') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.date') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($analyses as $analysis)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <div class="text-sm font-medium text-gray-900 max-w-xs truncate" title="{{ $analysis->url }}">
                                                        {{ $analysis->url }}
                                                    </div>
                                                    @if($analysis->title)
                                                        <div class="text-xs text-gray-500 max-w-xs truncate" title="{{ $analysis->title }}">
                                                            {{ $analysis->title }}
                                                        </div>
                                                    @endif
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
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($analysis->overall_score)
                                                    <div class="flex items-center">
                                                        <span class="font-medium text-sm">{{ number_format($analysis->overall_score, 1) }}</span>
                                                        <div class="ml-2 w-16 h-2 bg-gray-200 rounded-full">
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
                                                    <span class="text-gray-400 text-sm">--</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $analysis->technical_score ? number_format($analysis->technical_score, 1) : '--' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $analysis->content_score ? number_format($analysis->content_score, 1) : '--' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex flex-col">
                                                    <span>{{ $analysis->created_at->format('M j, Y') }}</span>
                                                    <span class="text-xs">{{ $analysis->created_at->format('H:i') }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if($analysis->status === 'completed')
                                                    <div class="flex space-x-2">
                                                        <a href="{{ route('analysis.show', $analysis->id) }}"
                                                           class="text-indigo-600 hover:text-indigo-900">{{ __('ui.view') }}</a>
                                                        <span class="text-gray-300">|</span>
                                                        <a href="{{ route('analysis.compare') }}?analysis1={{ $analysis->id }}"
                                                           class="text-green-600 hover:text-green-900">{{ __('analysis.compare') }}</a>
                                                        <span class="text-gray-300">|</span>
                                                        <a href="{{ route('analysis.export-pdf', $analysis->id) }}"
                                                           class="text-blue-600 hover:text-blue-900">PDF</a>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">--</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $analyses->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('analysis.no_analyses_found') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if(request()->hasAny(['search', 'status', 'score_range']))
                                    {{ __('analysis.try_adjusting_filters') }} <a href="{{ route('analysis.history') }}" class="text-indigo-600 hover:text-indigo-500">{{ __('analysis.clear_all_filters') }}</a>.
                                @else
                                    {{ __('analysis.get_started_by') }} <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-500">{{ __('analysis.analyzing_first_url') }}</a>.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection