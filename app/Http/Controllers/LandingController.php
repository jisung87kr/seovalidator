<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $usageInfo = null;

        // Only show usage info for guests
        if (!Auth::check()) {
            $identifier = $this->getUserIdentifier($request);
            $cacheKey = "daily_analysis_limit:{$identifier}:" . now()->format('Y-m-d');
            $currentUsage = Cache::get($cacheKey, 0);

            $usageInfo = [
                'used' => $currentUsage,
                'limit' => 3,
                'remaining' => max(0, 3 - $currentUsage),
                'reset_time' => now()->addDay()->startOfDay()
            ];
        }

        return view('landing.index', compact('usageInfo'));
    }

    private function getUserIdentifier(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
}