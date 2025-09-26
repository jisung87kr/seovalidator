<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use App\Services\SeoAnalyzerService;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\UrlValidator;
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

        $this->app->singleton(SeoAnalyzerService::class, function ($app) {
            return new SeoAnalyzerService(
                $app->make(CrawlerService::class),
                $app->make(UrlValidator::class),
                $app->make(HtmlParserService::class),
                $app->make(ScoreCalculatorService::class)
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
            return app()->environment('local') || app()->environment('staging');
        });
    }
}
