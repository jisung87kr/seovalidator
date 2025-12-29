@extends('layouts.app')

@if($analysis->status !== 'completed' && $analysis->status !== 'failed')
@push('scripts')
<script>
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
@endpush
@endif

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
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <span class="hidden sm:inline">{{ __('guest.sign_up_for_more') }}</span>
                                <span class="sm:hidden ml-1">{{ __('guest.sign_up') }}</span>
                            </a>
                        @endif
                        <a href="{{ route('guest.analyses') }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            <span class="hidden sm:inline">{{ __('guest.back_to_analyses') }}</span>
                            <span class="sm:hidden">{{ __('guest.back') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            @include('analysis.partials.results')
        </div>
    </div>
@endsection