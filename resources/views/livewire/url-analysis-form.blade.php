<div class="bg-white shadow rounded-lg p-6">
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.analyze_url') }}</h2>
        <p class="text-sm text-gray-600">{{ __('dashboard.enter_url_to_start') }}</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="analyzeUrl" class="space-y-4">
        <div>
            <label for="url" class="block text-sm font-medium text-gray-700">
                {{ __('dashboard.website_url') }}
            </label>
            <div class="mt-1 relative">
                <input
                    type="url"
                    id="url"
                    wire:model.live.debounce.500ms="url"
                    placeholder="{{ __('dashboard.url_placeholder') }}"
                    class="block w-full px-3 py-2 border {{ $errors && $errors->has('url') ? 'border-red-300' : 'border-gray-300' }} rounded-md shadow-sm placeholder-gray-400 focus:outline-none {{ $errors && $errors->has('url') ? 'focus:ring-red-500 focus:border-red-500 text-red-900 placeholder-red-300' : 'focus:ring-indigo-500 focus:border-indigo-500' }}"
                    {{ $isAnalyzing ? 'disabled' : '' }}
                >
                @if ($url && (!$errors || !$errors->has('url')))
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                @endif
            </div>
            @error('url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="analyzeUrl"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                {{ $isAnalyzing || !$url || ($errors && $errors->has('url')) ? 'disabled' : '' }}
            >
                <span wire:loading.remove wire:target="analyzeUrl" class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    {{ __('dashboard.analyze_url_button') }}
                </span>
                <span wire:loading wire:target="analyzeUrl" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('dashboard.analyzing') }}
                </span>
            </button>

            @if ($currentAnalysis || $isAnalyzing)
                <button
                    type="button"
                    wire:click="resetForm"
                    class="ml-3 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    {{ __('dashboard.new_analysis') }}
                </button>
            @endif
        </div>
    </form>

    @if ($currentAnalysis)
        <div class="mt-6 border-t border-gray-200 pt-6">
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            {{ __('dashboard.analysis_started') }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>{{ __('dashboard.url_label') }}: <span class="font-mono">{{ $currentAnalysis['url'] }}</span></p>
                            <p>{{ __('dashboard.status_label') }}: <span class="capitalize">{{ $currentAnalysis['status'] }}</span></p>
                            <p>{{ __('dashboard.started_label') }}: {{ $currentAnalysis['created_at'] }}</p>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center">
                                <div class="animate-pulse flex space-x-1">
                                    <div class="rounded-full bg-blue-400 h-2 w-2"></div>
                                    <div class="rounded-full bg-blue-400 h-2 w-2"></div>
                                    <div class="rounded-full bg-blue-400 h-2 w-2"></div>
                                </div>
                                <span class="ml-3 text-sm text-blue-600">{{ __('dashboard.processing') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('analysis-started', (data) => {
                console.log('Analysis started:', data);
                // Here you could show a toast notification, update other components, etc.
            });
        });
    </script>
</div>
