<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\GuestAnalysisController;
use Illuminate\Support\Facades\Route;

// Korean routes (default, no prefix)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// English routes (with /en prefix)
Route::prefix('en')->name('en.')->group(function () {
    Route::get('/', [LandingController::class, 'index'])->name('landing');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/analysis/export-comparison-pdf', [AnalysisController::class, 'exportComparisonPdf'])->name('analysis.export-comparison-pdf');
    Route::get('/analysis/{id}/export-pdf', [AnalysisController::class, 'exportPdf'])->name('analysis.export-pdf');
    Route::get('/analysis/history', [AnalysisController::class, 'history'])->name('analysis.history');
    Route::get('/analysis/compare', [AnalysisController::class, 'compare'])->name('analysis.compare');
    Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');

    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('user.profile');
    Route::put('/user/profile', [UserProfileController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/user/password', [UserProfileController::class, 'updatePassword'])->name('user.password.update');
    Route::put('/user/notifications', [UserProfileController::class, 'updateNotifications'])->name('user.notifications.update');
    Route::get('/user/export-data', [UserProfileController::class, 'exportData'])->name('user.export-data');

    Route::get('/user/api-keys', function () {
        return view('dashboard.api-keys');
    })->name('user.api-keys');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Guest analysis routes
Route::prefix('guest')->name('guest.')->group(function () {
    Route::get('/analyses', [GuestAnalysisController::class, 'index'])->name('analyses');
    Route::get('/analyses/{id}', [GuestAnalysisController::class, 'show'])->name('analyses.show');
    Route::post('/analyze', [GuestAnalysisController::class, 'analyze'])->name('analyze');
});

Route::get('/demo/analyze-url', function () {
    $url = request('url', 'https://example.com');

    \App\Jobs\CrawlUrl::dispatch($url, null); // null for guest users

    return response()->json([
        'message' => 'SEO analysis job dispatched',
        'url' => $url,
        'monitor_at' => url('/horizon')
    ]);
})->middleware('daily.limit');

// Health Check Routes (temporarily commented until HealthController exists)
/*
Route::prefix('health')->group(function () {
    Route::get('/', [App\Http\Controllers\HealthController::class, 'index']);
    Route::get('/comprehensive', [App\Http\Controllers\HealthController::class, 'comprehensive']);
    Route::get('/database', [App\Http\Controllers\HealthController::class, 'database']);
    Route::get('/cache', [App\Http\Controllers\HealthController::class, 'cache']);
    Route::get('/queue', [App\Http\Controllers\HealthController::class, 'queue']);
    Route::get('/storage', [App\Http\Controllers\HealthController::class, 'storage']);
    Route::get('/external', [App\Http\Controllers\HealthController::class, 'external']);
    Route::get('/performance', [App\Http\Controllers\HealthController::class, 'performance']);
    Route::get('/seo-analysis', [App\Http\Controllers\HealthController::class, 'seoAnalysis']);
});

// Kubernetes Health Probes
Route::get('/ready', [App\Http\Controllers\HealthController::class, 'ready']);
Route::get('/live', [App\Http\Controllers\HealthController::class, 'live']);

// Metrics endpoint
Route::get('/metrics', [App\Http\Controllers\HealthController::class, 'metrics']);
*/


require __DIR__.'/auth.php';
