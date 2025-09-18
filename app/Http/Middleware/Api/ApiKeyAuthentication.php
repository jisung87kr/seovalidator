<?php

namespace App\Http\Middleware\Api;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MISSING_API_KEY',
                    'message' => 'API key is required. Please provide X-API-Key header.'
                ]
            ], 401);
        }

        // Find API key in database
        $validApiKey = ApiKey::active()
            ->get()
            ->first(function ($key) use ($apiKey) {
                try {
                    return Crypt::decrypt($key->key) === $apiKey;
                } catch (\Exception $e) {
                    return false;
                }
            });

        if (!$validApiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid or expired API key.'
                ]
            ], 401);
        }

        // Store API key info in request for later use
        $request->attributes->set('api_key', $validApiKey);

        return $next($request);
    }
}