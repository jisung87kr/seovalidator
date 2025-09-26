<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
    @if($analysis && $analysis['status'] === 'completed')
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SEO Analysis Results</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $analysis['url'] }}</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-{{ $this->getScoreColor($analysis['overall_score']) }}-600">
                        {{ $analysis['overall_score'] }}/100
                    </div>
                    <div class="text-sm text-gray-500">
                        Grade: {{ $this->getScoreGrade($analysis['overall_score']) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Score Circle -->
        <div class="flex justify-center mb-8">
            <div class="relative w-32 h-32">
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                    <!-- Background circle -->
                    <circle cx="60" cy="60" r="54" fill="none" stroke="currentColor" stroke-width="12" class="text-gray-200 dark:text-gray-700"></circle>
                    <!-- Progress circle -->
                    <circle
                        cx="60"
                        cy="60"
                        r="54"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="12"
                        stroke-linecap="round"
                        class="text-{{ $this->getScoreColor($analysis['overall_score']) }}-500"
                        style="stroke-dasharray: {{ 2 * pi() * 54 }}; stroke-dashoffset: {{ 2 * pi() * 54 * (1 - $analysis['overall_score'] / 100) }}"
                    ></circle>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $analysis['overall_score'] }}</span>
                </div>
            </div>
        </div>

        <!-- Score Breakdown -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-{{ $this->getScoreColor($analysis['technical_score']) }}-600">
                    {{ $analysis['technical_score'] }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Technical SEO</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-{{ $this->getScoreColor($analysis['technical_score']) }}-600 h-2 rounded-full" style="width: {{ $analysis['technical_score'] }}%"></div>
                </div>
            </div>

            <div class="text-center">
                <div class="text-2xl font-bold text-{{ $this->getScoreColor($analysis['content_score']) }}-600">
                    {{ $analysis['content_score'] }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Content</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-{{ $this->getScoreColor($analysis['content_score']) }}-600 h-2 rounded-full" style="width: {{ $analysis['content_score'] }}%"></div>
                </div>
            </div>

            <div class="text-center">
                <div class="text-2xl font-bold text-{{ $this->getScoreColor($analysis['performance_score']) }}-600">
                    {{ $analysis['performance_score'] }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Performance</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-{{ $this->getScoreColor($analysis['performance_score']) }}-600 h-2 rounded-full" style="width: {{ $analysis['performance_score'] }}%"></div>
                </div>
            </div>

            <div class="text-center">
                <div class="text-2xl font-bold text-{{ $this->getScoreColor($analysis['accessibility_score']) }}-600">
                    {{ $analysis['accessibility_score'] }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Accessibility</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-{{ $this->getScoreColor($analysis['accessibility_score']) }}-600 h-2 rounded-full" style="width: {{ $analysis['accessibility_score'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Toggle Details Button -->
        <div class="flex justify-center">
            <button
                wire:click="toggleDetails"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                {{ $showDetails ? 'Hide Details' : 'Show Details' }}
                <svg class="ml-2 -mr-1 h-4 w-4 transform {{ $showDetails ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>

        <!-- Detailed Breakdown -->
        @if($showDetails)
            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Technical SEO Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Technical SEO</h4>
                        <div class="space-y-3">
                            @foreach($analysis['analysis_data']['technical'] as $item => $data)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $item) }}</span>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-{{ $this->getScoreColor($data['score']) }}-600">{{ $data['score'] }}/100</span>
                                        @if($data['issues'] > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">
                                                {{ $data['issues'] }} issues
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Content Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Content Analysis</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Word Count</span>
                                <span class="text-sm font-medium">{{ $analysis['analysis_data']['content']['word_count'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Readability</span>
                                <span class="text-sm font-medium text-{{ $this->getScoreColor($analysis['analysis_data']['content']['readability']) }}-600">
                                    {{ $analysis['analysis_data']['content']['readability'] }}/100
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Keyword Density</span>
                                <span class="text-sm font-medium">{{ $analysis['analysis_data']['content']['keyword_density'] }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Performance</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Page Size</span>
                                <span class="text-sm font-medium">{{ $analysis['analysis_data']['performance']['page_size'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Load Time</span>
                                <span class="text-sm font-medium">{{ $analysis['analysis_data']['performance']['load_time'] }}s</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-300">HTTP Requests</span>
                                <span class="text-sm font-medium">{{ $analysis['analysis_data']['performance']['requests'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Accessibility Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Accessibility</h4>
                        <div class="space-y-3">
                            @foreach($analysis['analysis_data']['accessibility'] as $item => $score)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $item) }}</span>
                                    <span class="text-sm font-medium text-{{ $this->getScoreColor($score) }}-600">{{ $score }}/100</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="flex space-x-3">
                <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Report
                </button>
                <button class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    Share
                </button>
                <button class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Re-analyze
                </button>
            </div>
        </div>

    @elseif($analysis && $analysis['status'] === 'processing')
        <!-- Processing State -->
        <div class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Analyzing {{ $analysis['url'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400">This may take a few moments...</p>
        </div>
    @else
        <!-- No Analysis State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No analysis yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start by analyzing a URL above.</p>
        </div>
    @endif
</div>