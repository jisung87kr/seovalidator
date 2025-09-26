<?php

use App\Http\Controllers\Api\V1\SeoAnalysisController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Version 1 of the SEO Validator API routes.
| All routes are prefixed with /api/v1 and require authentication.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // SEO Analysis endpoints
    Route::prefix('seo')->name('seo.')->middleware('throttle:api-analysis')->group(function () {
        // Single URL analysis
        Route::post('/analyze', [SeoAnalysisController::class, 'analyze'])->name('analyze');

        // Batch URL analysis
        Route::post('/analyze/batch', [SeoAnalysisController::class, 'analyzeBatch'])->name('batch.analyze');

        // Analysis history
        Route::get('/history', [SeoAnalysisController::class, 'history'])->name('history');
        Route::get('/history/{id}', [SeoAnalysisController::class, 'getAnalysis'])->name('analysis.show');

        // Analysis status (for async operations)
        Route::get('/status/{jobId}', [SeoAnalysisController::class, 'getStatus'])->name('status');

        // Batch status
        Route::get('/batch/{batch_id}', [SeoAnalysisController::class, 'getBatchStatus'])->name('batch.show');
    });

    // Webhook configuration endpoints
    Route::prefix('webhooks')->name('webhooks.')->middleware('throttle:api-webhooks')->group(function () {
        Route::get('/', [WebhookController::class, 'index'])->name('index');
        Route::post('/', [WebhookController::class, 'store'])->name('store');
        Route::get('/{webhook}', [WebhookController::class, 'show'])->name('show');
        Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
        Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
    });

    // API health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'status' => 'healthy'
        ]);
    })->name('health');
});