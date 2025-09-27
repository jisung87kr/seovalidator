<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalysisController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/analysis/history', [AnalysisController::class, 'history'])->name('analysis.history');
    Route::get('/analysis/compare', [AnalysisController::class, 'compare'])->name('analysis.compare');
    Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');

    Route::get('/user/profile', function () {
        return view('dashboard.profile');
    })->name('user.profile');

    Route::get('/user/api-keys', function () {
        return view('dashboard.api-keys');
    })->name('user.api-keys');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/demo/analyze-url', function () {
    $url = request('url', 'https://example.com');

    \App\Jobs\CrawlUrl::dispatch($url, 1); // Use dummy user ID for now

    return response()->json([
        'message' => 'SEO analysis job dispatched',
        'url' => $url,
        'monitor_at' => url('/horizon')
    ]);
});

// Health Check Routes
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


require __DIR__.'/auth.php';
