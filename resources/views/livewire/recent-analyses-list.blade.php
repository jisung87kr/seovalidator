<div>
    @if(count($analyses) > 0)
        <div class="overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($analyses as $analysis)
                    <li class="py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    @if($analysis['status'] === 'completed')
                                        <div class="w-10 h-10 rounded-full bg-{{ $this->getScoreColor($analysis['overall_score']) }}-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-{{ $this->getScoreColor($analysis['overall_score']) }}-600">
                                                {{ $analysis['overall_score'] }}
                                            </span>
                                        </div>
                                    @elseif($analysis['status'] === 'processing')
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $analysis['url'] }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $analysis['status'] === 'processing' ? 'Processing...' : 'Analyzed' }}
                                        {{ \Carbon\Carbon::parse($analysis['analyzed_at'])->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if($analysis['status'] === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getScoreColor($analysis['overall_score']) }}-100 text-{{ $this->getScoreColor($analysis['overall_score']) }}-800">
                                        Score: {{ $analysis['overall_score'] }}/100
                                    </span>
                                @elseif($analysis['status'] === 'processing')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Processing
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($analysis['status']) }}
                                    </span>
                                @endif

                                <button
                                    wire:click="viewAnalysis({{ $analysis['id'] }})"
                                    class="inline-flex items-center p-1 border border-transparent rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            <a href="{{ route('analysis.history') }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                View All Analyses
                <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    @else
        <div class="text-center py-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No analyses yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by analyzing your first URL.</p>
        </div>
    @endif
</div>
