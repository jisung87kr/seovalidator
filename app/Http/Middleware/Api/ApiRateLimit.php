<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'message' => 'API authentication required before rate limiting.'
                ]
            ], 401);
        }

        $key = 'api_rate_limit:' . $apiKey->id;
        $maxAttempts = $apiKey->rate_limit;
        $decayMinutes = 60; // 1 hour window

        $rateLimiter = RateLimiter::for($key, function () use ($maxAttempts, $decayMinutes) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes($decayMinutes, $maxAttempts);
        });

        if ($rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $rateLimiter->availableIn($key);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'details' => [
                        'retry_after' => $retryAfter,
                        'limit' => $maxAttempts,
                        'window' => '1 hour'
                    ]
                ]
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
                'Retry-After' => $retryAfter
            ]);
        }

        $rateLimiter->hit($key, $decayMinutes * 60);
        $remaining = $maxAttempts - $rateLimiter->attempts($key);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => now()->addHour()->timestamp
        ]);
    }
}