@extends('layouts.app')

@section('title', __('guest.my_analyses'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('guest.my_analyses') }}</h1>
                    <p class="text-gray-600">{{ __('guest.analyses_subtitle') }}</p>
                </div>
                <a href="{{ route('landing') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('guest.back_to_home') }}
                </a>
            </div>

            <!-- Usage Info -->
            @if($usageInfo)
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-sm font-medium text-gray-700">
                                {{ __('landing.daily_limit_info', ['used' => $usageInfo['used'], 'limit' => $usageInfo['limit']]) }}
                            </span>
                            @if($usageInfo['remaining'] > 0)
                                <span class="ml-2 text-sm text-gray-500">
                                    ({{ __('guest.remaining_today', ['count' => $usageInfo['remaining']]) }})
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ __('guest.resets_at', ['time' => $usageInfo['reset_time']->format('H:i')]) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 rounded-full h-2 transition-all duration-300"
                             style="width: {{ ($usageInfo['used'] / $usageInfo['limit']) * 100 }}%"></div>
                    </div>

                    @if($usageInfo['remaining'] <= 0)
                        <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                            <p class="text-sm text-orange-800">
                                {{ __('guest.limit_reached') }}
                                <a href="{{ route('register') }}" class="font-medium underline hover:no-underline">
                                    {{ __('guest.sign_up_unlimited') }}
                                </a>
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Quick Analysis Form -->
            @if($usageInfo['remaining'] > 0)
                <div class="mt-6 p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-100">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('guest.new_analysis') }}
                    </h3>
                    
                    <form id="quick-analysis-form" action="{{ route('guest.analyze') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('landing.enter_website_url') }}
                            </label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <input type="url" 
                                           id="url" 
                                           name="url" 
                                           placeholder="https://example.com" 
                                           required
                                           value="{{ old('url') }}"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                    <div id="url-error" class="mt-2 text-sm text-red-600 hidden"></div>
                                    @error('url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" 
                                        id="analyze-btn"
                                        class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span id="btn-text" class="flex items-center">
                                        <svg id="btn-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <span id="btn-label">{{ __('landing.analyze_now') }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-600">
                            {{ __('guest.quick_analysis_info') }}
                        </p>
                    </form>
                </div>
            @else
                <div class="mt-6 p-6 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg border border-orange-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('guest.daily_limit_reached') }}</h3>
                    <p class="text-gray-600 mb-4">{{ __('guest.upgrade_for_unlimited') }}</p>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        {{ __('guest.sign_up_continue') }}
                    </a>
                </div>
            @endif
        </div>

        <!-- Analyses List -->
        @if($analyses->count() > 0)
            <div class="grid gap-4">
                @foreach($analyses as $analysis)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ parse_url($analysis->url, PHP_URL_HOST) ?: $analysis->url }}
                                    </h3>
                                    @if($analysis->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ __('analysis.status_completed') }}
                                        </span>
                                    @elseif($analysis->status === 'processing')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ __('analysis.status_processing') }}
                                        </span>
                                    @elseif($analysis->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ __('analysis.status_failed') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ __('analysis.status_pending') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ $analysis->url }}</p>
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span>{{ __('guest.analyzed_at') }}: {{ $analysis->created_at->format('Y-m-d H:i') }}</span>
                                    @if($analysis->status === 'completed' && $analysis->overall_score)
                                        <span class="font-medium text-gray-700">
                                            {{ __('guest.score') }}: {{ $analysis->overall_score }}/100
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="ml-4">
                                @if($analysis->status === 'completed')
                                    <a href="{{ route('guest.analyses.show', $analysis->id) }}"
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                        {{ __('guest.view_results') }}
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @elseif($analysis->status === 'processing')
                                    <button disabled class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-600 text-sm font-medium rounded-lg cursor-not-allowed">
                                        <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('guest.processing') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($analyses->hasPages())
                <div class="mt-6">
                    {{ $analyses->links() }}
                </div>
            @endif
        @else
            <!-- Empty State with Analysis Form -->
            <div class="bg-white rounded-xl shadow-sm p-8 sm:p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('guest.no_analyses_yet') }}</h3>
                <p class="text-gray-600 mb-8">{{ __('guest.start_analyzing_desc') }}</p>
                
                @if($usageInfo['remaining'] > 0)
                    <!-- Quick Start Form -->
                    <div class="max-w-md mx-auto">
                        <form id="empty-analysis-form" action="{{ route('guest.analyze') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="text-left">
                                <label for="empty-url" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('landing.enter_website_url') }}
                                </label>
                                <input type="url" 
                                       id="empty-url" 
                                       name="url" 
                                       placeholder="https://example.com" 
                                       required
                                       value="{{ old('url') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <div id="empty-url-error" class="mt-2 text-sm text-red-600 hidden"></div>
                                @error('url')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" 
                                    id="empty-analyze-btn"
                                    class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span id="empty-btn-text" class="flex items-center">
                                    <svg id="empty-btn-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span id="empty-btn-label">{{ __('guest.analyze_first_url') }}</span>
                                </span>
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        {{ __('guest.sign_up_continue') }}
                    </a>
                @endif
            </div>
        @endif

        <!-- Sign Up CTA -->
        @if($analyses->count() > 0 && !auth()->check())
            <div class="mt-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-8 text-white text-center">
                <h2 class="text-2xl font-bold mb-4">{{ __('guest.unlock_full_features') }}</h2>
                <p class="text-lg mb-6 opacity-95">{{ __('guest.sign_up_benefits') }}</p>
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('guest.unlimited_analyses') }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('guest.save_history') }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ __('guest.compare_results') }}
                    </div>
                </div>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-8 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('guest.create_free_account') }}
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh for processing analyses
    @if($analyses->where('status', 'processing')->count() > 0)
        setTimeout(() => {
            window.location.reload();
        }, 5000);
    @endif

    // AJAX form submission for guest analysis
    document.addEventListener('DOMContentLoaded', function() {
        // Get form elements
        const quickForm = document.getElementById('quick-analysis-form');
        const emptyForm = document.getElementById('empty-analysis-form');

        // Setup AJAX for quick form
        if (quickForm) {
            quickForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const url = document.getElementById('url').value;
                const btn = document.getElementById('analyze-btn');
                const btnIcon = document.getElementById('btn-icon');
                const btnLabel = document.getElementById('btn-label');
                const errorEl = document.getElementById('url-error');
                
                submitAnalysis(url, btn, btnIcon, btnLabel, errorEl);
            });
        }

        // Setup AJAX for empty form
        if (emptyForm) {
            emptyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const url = document.getElementById('empty-url').value;
                const btn = document.getElementById('empty-analyze-btn');
                const btnIcon = document.getElementById('empty-btn-icon');
                const btnLabel = document.getElementById('empty-btn-label');
                const errorEl = document.getElementById('empty-url-error');
                
                submitAnalysis(url, btn, btnIcon, btnLabel, errorEl);
            });
        }

        function submitAnalysis(url, btn, btnIcon, btnLabel, errorEl) {
            // Clear previous errors
            errorEl.classList.add('hidden');
            errorEl.textContent = '';

            // Show loading state
            btn.disabled = true;
            btnIcon.innerHTML = `
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            `;
            btnIcon.classList.add('animate-spin');
            btnLabel.textContent = '{{ __("guest.processing") }}...';
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            // Submit AJAX request
            fetch('{{ route("guest.analyze") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ url: url })
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('{{ __("guest.analysis_started") }}', 'success');
                    
                    // Redirect to analyses page after a short delay
                    setTimeout(() => {
                        window.location.href = '{{ route("guest.analyses") }}';
                    }, 1500);
                } else {
                    // Show error
                    if (data.errors && data.errors.url) {
                        errorEl.textContent = data.errors.url[0];
                        errorEl.classList.remove('hidden');
                    } else {
                        showNotification(data.message || '{{ __("guest.analysis_error") }}', 'error');
                    }
                    resetButton(btn, btnIcon, btnLabel);
                }
            })
            .catch(error => {
                showNotification('{{ __("guest.network_error") }}', 'error');
                resetButton(btn, btnIcon, btnLabel);
            });
        }

        function resetButton(btn, btnIcon, btnLabel) {
            btn.disabled = false;
            btnIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>`;
            btnIcon.classList.remove('animate-spin');
            btnLabel.textContent = '{{ __("landing.analyze_now") }}';
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        ${type === 'success' 
                            ? '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>'
                            : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
                        }
                    </svg>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
    });
</script>
@endpush
@endsection
