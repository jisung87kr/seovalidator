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
        // Check if locale is provided in the request
        if ($request->has('locale') && in_array($request->locale, ['ko', 'en'])) {
            Session::put('locale', $request->locale);
        }

        // Set locale from session or default
        $locale = Session::get('locale', config('app.locale'));

        // Ensure the locale is supported
        if (in_array($locale, ['ko', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
