<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    Route::get('/analysis/history', function () {
        return view('dashboard.history');
    })->name('analysis.history');

    Route::get('/analysis/compare', function () {
        return view('dashboard.compare');
    })->name('analysis.compare');

    Route::get('/analysis/{id}', function ($id) {
        return view('dashboard.analysis', compact('id'));
    })->name('analysis.show');

    Route::get('/user/profile', function () {
        return view('dashboard.profile');
    })->name('user.profile');

    Route::get('/user/api-keys', function () {
        return view('dashboard.api-keys');
    })->name('user.api-keys');
});

// Auth Routes (temporary placeholders)
Route::post('/logout', function () {
    return redirect('/');
})->name('logout');

// Demo route to test queue jobs
Route::get('/demo/analyze-url', function () {
    $url = request('url', 'https://example.com');

    \App\Jobs\CrawlUrl::dispatch($url, 1); // Use dummy user ID for now

    return response()->json([
        'message' => 'SEO analysis job dispatched',
        'url' => $url,
        'monitor_at' => url('/horizon')
    ]);
});
