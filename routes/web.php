<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalysisController;
use App\Models\SeoAnalysis;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Barryvdh\Snappy\Facades\SnappyPdf;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

route::get('/test', function(){
    // 한글이 포함된 HTML을 직접 테스트
    $html = '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: "DejaVu Sans", serif; font-size: 16px; }
    </style>
</head>
<body>
    <h1>SEO 분석 결과</h1>
    <p>URL: https://example.com</p>
    <p>분석 날짜: 2024-01-01</p>
    <p>전체 점수: 85점</p>
    <p>기술적 SEO: 좋음</p>
    <p>콘텐츠 품질: 우수</p>
    <p>성능: 보통</p>
    <p>접근성: 양호</p>
</body>
</html>';
    
    return SnappyPdf::loadHTML($html)
        ->setOption('page-size', 'A4')
        ->setOption('orientation', 'portrait')
        ->setOption('encoding', 'utf-8')
        ->stream('seo-analysis.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/analysis/history', [AnalysisController::class, 'history'])->name('analysis.history');
    Route::get('/analysis/compare', [AnalysisController::class, 'compare'])->name('analysis.compare');
    Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::get('/analysis/{id}/export-pdf', [AnalysisController::class, 'exportPdf'])->name('analysis.export-pdf');
    Route::post('/analysis/export-comparison-pdf', [AnalysisController::class, 'exportComparisonPdf'])->name('analysis.export-comparison-pdf');

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
