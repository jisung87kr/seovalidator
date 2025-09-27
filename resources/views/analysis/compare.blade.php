@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Compare SEO Analyses</h1>
                        <p class="mt-2 text-gray-600">Compare two SEO analysis results side by side</p>
                    </div>
                    <a href="{{ route('analysis.history') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Back to History
                    </a>
                </div>
            </div>

            <!-- Selection Form -->
            <div class="bg-white shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">Select Analyses to Compare</h2>
                    <form method="GET" action="{{ route('analysis.compare') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
                        <div class="flex-1">
                            <label for="analysis1" class="block text-sm font-medium text-gray-700">First Analysis</label>
                            <select id="analysis1" name="analysis1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select an analysis...</option>
                                @foreach($availableAnalyses as $analysis)
                                    <option value="{{ $analysis->id }}" {{ request('analysis1') == $analysis->id ? 'selected' : '' }}>
                                        {{ $analysis->url }} ({{ $analysis->overall_score ? number_format($analysis->overall_score, 1) : '--' }}) - {{ $analysis->created_at->format('M j, Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1">
                            <label for="analysis2" class="block text-sm font-medium text-gray-700">Second Analysis</label>
                            <select id="analysis2" name="analysis2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select an analysis...</option>
                                @foreach($availableAnalyses as $analysis)
                                    <option value="{{ $analysis->id }}" {{ request('analysis2') == $analysis->id ? 'selected' : '' }}>
                                        {{ $analysis->url }} ({{ $analysis->overall_score ? number_format($analysis->overall_score, 1) : '--' }}) - {{ $analysis->created_at->format('M j, Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Compare
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($comparison)
                <!-- Comparison Results -->
                <div class="space-y-8">
                    <!-- Score Comparison -->
                    <div class="bg-white shadow-sm rounded-lg">
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-6">Score Comparison</h2>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Analysis 1 -->
                                <div class="text-center">
                                    <h3 class="text-md font-medium text-gray-700 mb-4 truncate" title="{{ $comparison['analysis1']->url }}">
                                        {{ $comparison['analysis1']->url }}
                                    </h3>
                                    <div class="relative mb-4">
                                        <div class="w-24 h-24 mx-auto rounded-full border-4 border-gray-200 flex items-center justify-center
                                            @if($comparison['analysis1']->overall_score >= 90) border-green-500
                                            @elseif($comparison['analysis1']->overall_score >= 70) border-blue-500
                                            @elseif($comparison['analysis1']->overall_score >= 50) border-yellow-500
                                            @else border-red-500
                                            @endif">
                                            <div class="text-center">
                                                <div class="text-xl font-bold text-gray-900">{{ number_format($comparison['analysis1']->overall_score, 1) }}</div>
                                                <div class="text-xs text-gray-500">/ 100</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span>Technical:</span>
                                            <span class="font-medium">{{ $comparison['analysis1']->technical_score ? number_format($comparison['analysis1']->technical_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Content:</span>
                                            <span class="font-medium">{{ $comparison['analysis1']->content_score ? number_format($comparison['analysis1']->content_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Performance:</span>
                                            <span class="font-medium">{{ $comparison['analysis1']->performance_score ? number_format($comparison['analysis1']->performance_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Accessibility:</span>
                                            <span class="font-medium">{{ $comparison['analysis1']->accessibility_score ? number_format($comparison['analysis1']->accessibility_score, 1) : '--' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analysis 2 -->
                                <div class="text-center">
                                    <h3 class="text-md font-medium text-gray-700 mb-4 truncate" title="{{ $comparison['analysis2']->url }}">
                                        {{ $comparison['analysis2']->url }}
                                    </h3>
                                    <div class="relative mb-4">
                                        <div class="w-24 h-24 mx-auto rounded-full border-4 border-gray-200 flex items-center justify-center
                                            @if($comparison['analysis2']->overall_score >= 90) border-green-500
                                            @elseif($comparison['analysis2']->overall_score >= 70) border-blue-500
                                            @elseif($comparison['analysis2']->overall_score >= 50) border-yellow-500
                                            @else border-red-500
                                            @endif">
                                            <div class="text-center">
                                                <div class="text-xl font-bold text-gray-900">{{ number_format($comparison['analysis2']->overall_score, 1) }}</div>
                                                <div class="text-xs text-gray-500">/ 100</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span>Technical:</span>
                                            <span class="font-medium">{{ $comparison['analysis2']->technical_score ? number_format($comparison['analysis2']->technical_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Content:</span>
                                            <span class="font-medium">{{ $comparison['analysis2']->content_score ? number_format($comparison['analysis2']->content_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Performance:</span>
                                            <span class="font-medium">{{ $comparison['analysis2']->performance_score ? number_format($comparison['analysis2']->performance_score, 1) : '--' }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span>Accessibility:</span>
                                            <span class="font-medium">{{ $comparison['analysis2']->accessibility_score ? number_format($comparison['analysis2']->accessibility_score, 1) : '--' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Score Difference -->
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <h3 class="text-md font-medium text-gray-700 mb-4">Score Differences</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @php
                                        $diff = $comparison['analysis2']->overall_score - $comparison['analysis1']->overall_score;
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600">Overall</div>
                                        <div class="text-lg font-semibold {{ $diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}
                                        </div>
                                    </div>

                                    @php
                                        $techDiff = ($comparison['analysis2']->technical_score ?? 0) - ($comparison['analysis1']->technical_score ?? 0);
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600">Technical</div>
                                        <div class="text-lg font-semibold {{ $techDiff > 0 ? 'text-green-600' : ($techDiff < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                            {{ $techDiff > 0 ? '+' : '' }}{{ number_format($techDiff, 1) }}
                                        </div>
                                    </div>

                                    @php
                                        $contentDiff = ($comparison['analysis2']->content_score ?? 0) - ($comparison['analysis1']->content_score ?? 0);
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600">Content</div>
                                        <div class="text-lg font-semibold {{ $contentDiff > 0 ? 'text-green-600' : ($contentDiff < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                            {{ $contentDiff > 0 ? '+' : '' }}{{ number_format($contentDiff, 1) }}
                                        </div>
                                    </div>

                                    @php
                                        $perfDiff = ($comparison['analysis2']->performance_score ?? 0) - ($comparison['analysis1']->performance_score ?? 0);
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600">Performance</div>
                                        <div class="text-lg font-semibold {{ $perfDiff > 0 ? 'text-green-600' : ($perfDiff < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                            {{ $perfDiff > 0 ? '+' : '' }}{{ number_format($perfDiff, 1) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Information Comparison -->
                    @if(isset($comparison['data1']['seo_elements']['meta']) && isset($comparison['data2']['seo_elements']['meta']))
                        <div class="bg-white shadow-sm rounded-lg">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-gray-900 mb-6">Page Information Comparison</h2>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <!-- Analysis 1 Meta -->
                                    <div>
                                        <h3 class="text-md font-medium text-gray-700 mb-4">{{ parse_url($comparison['analysis1']->url, PHP_URL_HOST) }}</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-600 mb-2">Title</h4>
                                                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                                    {{ $comparison['data1']['seo_elements']['meta']['title'] ?? 'No title found' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Length: {{ $comparison['data1']['seo_elements']['meta']['title_length'] ?? 0 }} characters
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-600 mb-2">Description</h4>
                                                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                                    {{ $comparison['data1']['seo_elements']['meta']['description'] ?? 'No description found' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Length: {{ $comparison['data1']['seo_elements']['meta']['description_length'] ?? 0 }} characters
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Analysis 2 Meta -->
                                    <div>
                                        <h3 class="text-md font-medium text-gray-700 mb-4">{{ parse_url($comparison['analysis2']->url, PHP_URL_HOST) }}</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-600 mb-2">Title</h4>
                                                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                                    {{ $comparison['data2']['seo_elements']['meta']['title'] ?? 'No title found' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Length: {{ $comparison['data2']['seo_elements']['meta']['title_length'] ?? 0 }} characters
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-600 mb-2">Description</h4>
                                                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                                    {{ $comparison['data2']['seo_elements']['meta']['description'] ?? 'No description found' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Length: {{ $comparison['data2']['seo_elements']['meta']['description_length'] ?? 0 }} characters
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Technical Comparison -->
                    @if(isset($comparison['data1']['crawl_data']) && isset($comparison['data2']['crawl_data']))
                        <div class="bg-white shadow-sm rounded-lg">
                            <div class="p-6">
                                <h2 class="text-lg font-medium text-gray-900 mb-6">Technical Comparison</h2>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analysis 1</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analysis 2</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Page Size</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ isset($comparison['data1']['crawl_data']['html_size']) ? number_format($comparison['data1']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ isset($comparison['data2']['crawl_data']['html_size']) ? number_format($comparison['data2']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    @if(isset($comparison['data1']['crawl_data']['html_size']) && isset($comparison['data2']['crawl_data']['html_size']))
                                                        @php
                                                            $sizeDiff = ($comparison['data2']['crawl_data']['html_size'] - $comparison['data1']['crawl_data']['html_size']) / 1024;
                                                        @endphp
                                                        <span class="{{ $sizeDiff > 0 ? 'text-red-600' : ($sizeDiff < 0 ? 'text-green-600' : 'text-gray-600') }}">
                                                            {{ $sizeDiff > 0 ? '+' : '' }}{{ number_format($sizeDiff, 1) }} KB
                                                        </span>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Load Time</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ isset($comparison['data1']['crawl_data']['load_time_ms']) ? number_format($comparison['data1']['crawl_data']['load_time_ms']) . ' ms' : '--' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ isset($comparison['data2']['crawl_data']['load_time_ms']) ? number_format($comparison['data2']['crawl_data']['load_time_ms']) . ' ms' : '--' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    @if(isset($comparison['data1']['crawl_data']['load_time_ms']) && isset($comparison['data2']['crawl_data']['load_time_ms']))
                                                        @php
                                                            $timeDiff = $comparison['data2']['crawl_data']['load_time_ms'] - $comparison['data1']['crawl_data']['load_time_ms'];
                                                        @endphp
                                                        <span class="{{ $timeDiff > 0 ? 'text-red-600' : ($timeDiff < 0 ? 'text-green-600' : 'text-gray-600') }}">
                                                            {{ $timeDiff > 0 ? '+' : '' }}{{ number_format($timeDiff) }} ms
                                                        </span>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                @if($availableAnalyses->count() < 2)
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Not enough analyses</h3>
                        <p class="mt-1 text-sm text-gray-500">You need at least 2 completed analyses to use the comparison feature.</p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Analyze More URLs
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Select analyses to compare</h3>
                        <p class="mt-1 text-sm text-gray-500">Choose two analyses from the dropdowns above to see a detailed comparison.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection