<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use App\Services\SeoAnalyzerService;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\UrlValidator;
use App\Services\Crawler\PageAnalyzer;
use App\Services\Parser\HtmlParserService;
use App\Services\Score\ScoreCalculatorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SEO Analysis Services
        $this->app->singleton(UrlValidator::class);
        $this->app->singleton(CrawlerService::class);
        $this->app->singleton(HtmlParserService::class);
        $this->app->singleton(ScoreCalculatorService::class);
        $this->app->singleton(PageAnalyzer::class);

        $this->app->singleton(SeoAnalyzerService::class, function ($app) {
            return new SeoAnalyzerService(
                $app->make(CrawlerService::class),
                $app->make(UrlValidator::class),
                $app->make(HtmlParserService::class),
                $app->make(ScoreCalculatorService::class),
                $app->make(PageAnalyzer::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Horizon authorization
        Horizon::auth(function ($request) {
            // In production, you should implement proper authentication
            return in_array(config('app.env'), ['local', 'staging']);
        });

        // Configure API rate limiting
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for different API endpoints
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // SEO Analysis rate limiting (more restrictive)
        RateLimiter::for('api-analysis', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Analysis rate limit exceeded. Maximum 10 analyses per minute.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Webhook configuration rate limiting
        RateLimiter::for('api-webhooks', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Webhook configuration rate limit exceeded. Maximum 20 requests per minute.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Batch analysis rate limiting (most restrictive)
        RateLimiter::for('api-batch', function (Request $request) {
            return Limit::perHour(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Batch analysis rate limit exceeded. Maximum 5 batch requests per hour.',
                        'retry_after' => $headers['Retry-After'] ?? 3600,
                    ], 429, $headers);
                });
        });
    }
}
