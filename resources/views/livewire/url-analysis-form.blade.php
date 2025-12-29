<div @if($isAnalyzing) wire:poll.2s="checkStatus" @endif>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-primary">{{ __('dashboard.analyze_url') }}</h2>
        <p class="text-sm text-content-secondary mt-1">{{ __('dashboard.enter_url_to_start') }}</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-success-light border border-success/20 text-success-dark rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="analyzeUrl" class="space-y-4">
        <div>
            <label for="url" class="block text-sm font-medium text-content mb-2">
                {{ __('dashboard.website_url') }}
            </label>
            <div class="relative">
                <input
                    type="url"
                    id="url"
                    wire:model.live.debounce.500ms="url"
                    placeholder="{{ __('dashboard.url_placeholder') }}"
                    class="w-full px-4 py-3 bg-surface-subtle border {{ $errors && $errors->has('url') ? 'border-error' : 'border-border' }} rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 {{ $errors && $errors->has('url') ? 'focus:ring-error' : 'focus:ring-accent' }} focus:border-transparent focus:bg-white transition-all"
                    {{ $isAnalyzing ? 'disabled' : '' }}
                >
                @if ($url && (!$errors || !$errors->has('url')))
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <svg class="h-5 w-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @endif
            </div>
            @error('url')
                <p class="mt-2 text-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="analyzeUrl"
                class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                {{ $isAnalyzing || !$url || ($errors && $errors->has('url')) ? 'disabled' : '' }}
            >
                <span wire:loading.remove wire:target="analyzeUrl" class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    {{ __('dashboard.analyze_url_button') }}
                </span>
                <span wire:loading wire:target="analyzeUrl" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
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
                    class="inline-flex items-center px-4 py-3 bg-white border border-border text-sm font-medium text-content-secondary rounded-xl hover:bg-surface-subtle transition-colors"
                >
                    {{ __('dashboard.new_analysis') }}
                </button>
            @endif
        </div>
    </form>

    @if ($currentAnalysis)
        <div class="mt-6 pt-6 border-t border-border">
            <div class="bg-accent-light rounded-xl p-5">
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">
                            <svg class="h-5 w-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-accent-dark">
                            {{ __('dashboard.analysis_started') }}
                        </h3>
                        <div class="mt-2 text-sm text-accent-dark/80 space-y-1">
                            <p><span class="font-medium">{{ __('dashboard.url_label') }}:</span> <span class="font-mono text-xs">{{ $currentAnalysis['url'] }}</span></p>
                            <p><span class="font-medium">{{ __('dashboard.status_label') }}:</span> <span class="capitalize">{{ $currentAnalysis['status'] }}</span></p>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <div class="flex gap-1">
                                <div class="w-2 h-2 bg-accent rounded-full animate-pulse"></div>
                                <div class="w-2 h-2 bg-accent rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                                <div class="w-2 h-2 bg-accent rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                            </div>
                            <span class="text-sm text-accent">{{ __('dashboard.processing') }}</span>
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
            });
        });
    </script>
</div>
