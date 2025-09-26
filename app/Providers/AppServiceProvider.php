<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
