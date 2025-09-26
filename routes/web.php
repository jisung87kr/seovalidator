<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SEO Validator Dashboard
Route::get('/dashboard', function () {
    return response()->json([
        'message' => 'SEO Validator Dashboard',
        'horizon_url' => url('/horizon'),
        'queues' => [
            'seo_crawling' => 'URL Crawling Queue',
            'seo_analysis' => 'SEO Analysis Queue',
            'seo_reporting' => 'Report Generation Queue'
        ]
    ]);
});

// Demo route to test queue jobs
Route::get('/demo/analyze-url', function () {
    $url = request('url', 'https://example.com');

    \App\Jobs\CrawlUrl::dispatch($url, auth()->id());

    return response()->json([
        'message' => 'SEO analysis job dispatched',
        'url' => $url,
        'monitor_at' => url('/horizon')
    ]);
});
