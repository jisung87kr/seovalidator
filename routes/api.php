<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SeoController;
use App\Http\Controllers\Api\V1\SystemController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/system/health', [SystemController::class, 'health']);
    Route::get('/system/status', [SystemController::class, 'status']);
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/revoke', [AuthController::class, 'revoke'])->middleware(['api.auth', 'api.rate_limit']);
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware(['api.auth', 'api.rate_limit']);
    });
    
    // Protected API routes
    Route::middleware(['api.auth', 'api.rate_limit'])->group(function () {
        // SEO Analysis routes
        Route::prefix('seo')->group(function () {
            Route::post('/analyze', [SeoController::class, 'analyze']);
            Route::get('/result/{id}', [SeoController::class, 'getResult']);
            Route::get('/progress/{id}', [SeoController::class, 'getProgress']);
            Route::get('/history', [SeoController::class, 'getHistory']);
            Route::delete('/result/{id}', [SeoController::class, 'deleteResult']);
        });
    });
});