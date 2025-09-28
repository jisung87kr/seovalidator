@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="py-6 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ __('ui.profile') }}</h1>
            <p class="mt-2 text-sm sm:text-base text-gray-600">{{ __('ui.manage_account_settings') }}</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-6">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">{{ __('ui.profile_information') }}</h2>
                <form method="POST" action="{{ route('user.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.full_name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.email_address') }}</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.company') }}</label>
                        <input type="text" name="company" value="{{ old('company', $user->company) }}" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('company')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.bio') }}</label>
                        <textarea name="bio" rows="3" placeholder="{{ __('ui.tell_us_about_yourself') }}" 
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('ui.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-6">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">{{ __('ui.change_password') }}</h2>
                <form method="POST" action="{{ route('user.password.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.current_password') }}</label>
                        <input type="password" name="current_password" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.new_password') }}</label>
                        <input type="password" name="password" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('ui.confirm_new_password') }}</label>
                        <input type="password" name="password_confirmation" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('ui.update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Statistics & Settings -->
        <div class="space-y-6">
            <!-- Account Stats -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">{{ __('ui.account_statistics') }}</h3>
                <div class="space-y-3 sm:space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ __('ui.total_analyses') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ $totalAnalyses }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ __('ui.completed_analyses') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ $completedAnalyses }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ __('ui.average_score') }}</span>
                        <span class="text-sm font-medium {{ $averageScore >= 70 ? 'text-green-600' : ($averageScore >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $averageScore ? number_format($averageScore, 1) : '--' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ __('ui.member_since') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">{{ __('ui.notifications') }}</h3>
                <form method="POST" action="{{ route('user.notifications.update') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('ui.email_notifications') }}</label>
                                <p class="text-sm text-gray-500">{{ __('ui.receive_analysis_results_email') }}</p>
                            </div>
                            <input type="checkbox" name="email_notifications" value="1" 
                                   {{ old('email_notifications', $user->email_notifications) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('ui.weekly_reports') }}</label>
                                <p class="text-sm text-gray-500">{{ __('ui.get_weekly_seo_reports') }}</p>
                            </div>
                            <input type="checkbox" name="weekly_reports" value="1" 
                                   {{ old('weekly_reports', $user->weekly_reports) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('ui.marketing_emails') }}</label>
                                <p class="text-sm text-gray-500">{{ __('ui.receive_product_updates') }}</p>
                            </div>
                            <input type="checkbox" name="marketing_emails" value="1" 
                                   {{ old('marketing_emails', $user->marketing_emails) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm">
                            {{ __('ui.save_preferences') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Plan Information -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">{{ __('ui.current_plan') }}</h3>
                <div class="text-center py-4">
                    <div class="text-xl sm:text-2xl font-bold text-indigo-600">{{ __('ui.free_plan') }}</div>
                    <div class="text-sm text-gray-500">{{ $monthlyLimit }} {{ __('ui.analyses_per_month') }}</div>
                    <div class="mt-4">
                        <div class="text-sm text-gray-600">{{ __('ui.usage_this_month') }}</div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usagePercentage, 100) }}%"></div>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">{{ $monthlyAnalyses }} / {{ $monthlyLimit }} {{ __('ui.used') }}</div>
                    </div>
                    <button class="mt-4 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm">
                        {{ __('ui.upgrade_plan') }}
                    </button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 border border-red-200">
                <h3 class="text-base sm:text-lg font-semibold text-red-600 mb-4">{{ __('ui.danger_zone') }}</h3>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">{{ __('ui.export_data') }}</h4>
                        <p class="text-sm text-gray-500 mb-2">{{ __('ui.download_all_analysis_data') }}</p>
                        <a href="{{ route('user.export-data') }}" class="inline-block text-sm bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-1 px-3 rounded">
                            {{ __('ui.export') }}
                        </a>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">{{ __('ui.delete_account') }}</h4>
                        <p class="text-sm text-gray-500 mb-2">{{ __('ui.permanently_delete_account') }}</p>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('{{ __('ui.confirm_delete_account') }}')"
                                    class="text-sm bg-red-100 hover:bg-red-200 text-red-800 font-medium py-1 px-3 rounded">
                                {{ __('ui.delete_account') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
