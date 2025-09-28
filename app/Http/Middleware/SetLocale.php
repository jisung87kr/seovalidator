<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the locale from URL path
        $segment = $request->segment(1);

        if ($segment === 'en') {
            $locale = 'en';
        } else {
            // Default to Korean for root path and all other paths
            $locale = 'ko';
        }

        if($request->input('locale')){
            $locale = $request->input('locale');
        }

        // Set the application locale
        App::setLocale($locale);

        // Store in session for consistency
        Session::put('locale', $locale);

        return $next($request);
    }
}
