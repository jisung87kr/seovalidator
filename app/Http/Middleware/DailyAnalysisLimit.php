<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DailyAnalysisLimit
{
    const DAILY_LIMIT = 3;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip limit for authenticated users
        if (Auth::check()) {
            return $next($request);
        }

        // Get user identifier (IP address for guests)
        $identifier = $this->getUserIdentifier($request);
        $cacheKey = "daily_analysis_limit:{$identifier}:" . now()->format('Y-m-d');

        // Get current usage count
        $currentCount = Cache::get($cacheKey, 0);

        // Check if limit exceeded
        if ($currentCount >= self::DAILY_LIMIT) {
            return response()->json([
                'error' => true,
                'message' => __('analysis.daily_limit_exceeded'),
                'limit' => self::DAILY_LIMIT,
                'remaining' => 0,
                'reset_time' => now()->addDay()->startOfDay()->toISOString(),
                'action_required' => 'register'
            ], 429);
        }

        // Increment counter
        Cache::put($cacheKey, $currentCount + 1, now()->addDay()->startOfDay());

        // Add usage info to response headers
        $response = $next($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            $data['usage'] = [
                'used' => $currentCount + 1,
                'limit' => self::DAILY_LIMIT,
                'remaining' => self::DAILY_LIMIT - ($currentCount + 1),
                'reset_time' => now()->addDay()->startOfDay()->toISOString()
            ];
            $response->setData($data);
        }

        return $response;
    }

    /**
     * Get user identifier for rate limiting
     */
    private function getUserIdentifier(Request $request): string
    {
        // Use IP address and User-Agent for better uniqueness while maintaining privacy
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
}
