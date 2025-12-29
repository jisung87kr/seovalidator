@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ __('ui.profile') }}</h1>
        <p class="mt-2 text-content-secondary">{{ __('ui.manage_account_settings') }}</p>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-success-light border border-success/20 text-success-dark rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-primary mb-6">{{ __('ui.profile_information') }}</h2>
                <form method="POST" action="{{ route('user.profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.full_name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                        @error('name')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.email_address') }}</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                        @error('email')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.company') }}</label>
                        <input type="text" name="company" value="{{ old('company', $user->company) }}"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                        @error('company')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.bio') }}</label>
                        <textarea name="bio" rows="3" placeholder="{{ __('ui.tell_us_about_yourself') }}"
                                  class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                            {{ __('ui.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-primary mb-6">{{ __('ui.change_password') }}</h2>
                <form method="POST" action="{{ route('user.password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.current_password') }}</label>
                        <input type="password" name="current_password"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                        @error('current_password')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.new_password') }}</label>
                        <input type="password" name="password"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                        @error('password')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-content mb-2">{{ __('ui.confirm_new_password') }}</label>
                        <input type="password" name="password_confirmation"
                               class="w-full px-4 py-3 bg-surface-subtle border border-border rounded-xl text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                            {{ __('ui.update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Stats -->
            <div class="bg-white rounded-2xl border border-border p-6">
                <h3 class="text-lg font-semibold text-primary mb-4">{{ __('ui.account_statistics') }}</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-content-secondary">{{ __('ui.total_analyses') }}</span>
                        <span class="text-sm font-semibold text-primary">{{ $totalAnalyses }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-content-secondary">{{ __('ui.completed_analyses') }}</span>
                        <span class="text-sm font-semibold text-primary">{{ $completedAnalyses }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-content-secondary">{{ __('ui.average_score') }}</span>
                        <span class="text-sm font-semibold {{ $averageScore >= 70 ? 'text-success' : ($averageScore >= 50 ? 'text-warning' : 'text-error') }}">
                            {{ $averageScore ? number_format($averageScore, 1) : '--' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-content-secondary">{{ __('ui.member_since') }}</span>
                        <span class="text-sm font-semibold text-primary">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white rounded-2xl border border-border p-6">
                <h3 class="text-lg font-semibold text-primary mb-4">{{ __('ui.notifications') }}</h3>
                <form method="POST" action="{{ route('user.notifications.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="email_notifications" value="1"
                                   {{ old('email_notifications', $user->email_notifications) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 text-accent focus:ring-accent border-border rounded">
                            <div>
                                <span class="text-sm font-medium text-primary">{{ __('ui.email_notifications') }}</span>
                                <p class="text-xs text-content-muted">{{ __('ui.receive_analysis_results_email') }}</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="weekly_reports" value="1"
                                   {{ old('weekly_reports', $user->weekly_reports) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 text-accent focus:ring-accent border-border rounded">
                            <div>
                                <span class="text-sm font-medium text-primary">{{ __('ui.weekly_reports') }}</span>
                                <p class="text-xs text-content-muted">{{ __('ui.get_weekly_seo_reports') }}</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="marketing_emails" value="1"
                                   {{ old('marketing_emails', $user->marketing_emails) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 text-accent focus:ring-accent border-border rounded">
                            <div>
                                <span class="text-sm font-medium text-primary">{{ __('ui.marketing_emails') }}</span>
                                <p class="text-xs text-content-muted">{{ __('ui.receive_product_updates') }}</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-accent hover:bg-accent-hover text-white text-sm font-medium rounded-xl transition-colors">
                            {{ __('ui.save_preferences') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Plan Information -->
            <div class="bg-white rounded-2xl border border-border p-6">
                <h3 class="text-lg font-semibold text-primary mb-4">{{ __('ui.current_plan') }}</h3>
                <div class="text-center py-4">
                    <div class="text-2xl font-bold text-accent">{{ __('ui.free_plan') }}</div>
                    <div class="text-sm text-content-muted mt-1">{{ $monthlyLimit }} {{ __('ui.analyses_per_month') }}</div>
                    <div class="mt-4">
                        <div class="text-xs text-content-secondary mb-2">{{ __('ui.usage_this_month') }}</div>
                        <div class="w-full bg-surface-subtle rounded-full h-2">
                            <div class="bg-accent h-2 rounded-full transition-all" style="width: {{ min($usagePercentage, 100) }}%"></div>
                        </div>
                        <div class="text-xs text-content-muted mt-2">{{ $monthlyAnalyses }} / {{ $monthlyLimit }} {{ __('ui.used') }}</div>
                    </div>
                    <button class="mt-4 w-full inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-xl transition-colors">
                        {{ __('ui.upgrade_plan') }}
                    </button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white rounded-2xl border border-error/30 p-6">
                <h3 class="text-lg font-semibold text-error mb-4">{{ __('ui.danger_zone') }}</h3>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-primary">{{ __('ui.export_data') }}</h4>
                        <p class="text-xs text-content-muted mt-1">{{ __('ui.download_all_analysis_data') }}</p>
                        <a href="{{ route('user.export-data') }}"
                           class="inline-flex items-center mt-2 px-3 py-1.5 bg-surface-subtle hover:bg-surface-muted text-sm font-medium text-content-secondary rounded-lg transition-colors">
                            {{ __('ui.export') }}
                        </a>
                    </div>
                    <div class="pt-4 border-t border-border">
                        <h4 class="text-sm font-medium text-primary">{{ __('ui.delete_account') }}</h4>
                        <p class="text-xs text-content-muted mt-1">{{ __('ui.permanently_delete_account') }}</p>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('{{ __('ui.confirm_delete_account') }}')"
                                    class="inline-flex items-center mt-2 px-3 py-1.5 bg-error-light hover:bg-error/20 text-sm font-medium text-error rounded-lg transition-colors">
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
