<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeUrl;
use App\Models\SeoAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GuestAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $identifier = $this->getUserIdentifier($request);
        $cacheKey = "guest_analyses:{$identifier}";

        // Get guest's analysis IDs from cache
        $analysisIds = Cache::get($cacheKey, []);

        // Fetch analyses
        $analyses = SeoAnalysis::whereIn('id', $analysisIds)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get usage info
        $usageCacheKey = "daily_analysis_limit:{$identifier}:" . now()->format('Y-m-d');
        $currentUsage = Cache::get($usageCacheKey, 0);

        $usageInfo = [
            'used' => $currentUsage,
            'limit' => 3,
            'remaining' => max(0, 3 - $currentUsage),
            'reset_time' => now()->addDay()->startOfDay()
        ];

        return view('guest.analyses', compact('analyses', 'usageInfo'));
    }

    public function show(Request $request, $id)
    {
        $identifier = $this->getUserIdentifier($request);
        $cacheKey = "guest_analyses:{$identifier}";

        // Get guest's analysis IDs from cache
        $analysisIds = Cache::get($cacheKey, []);

        // Check if this analysis belongs to the guest
        if (!in_array($id, $analysisIds)) {
            return redirect()->route('guest.analyses')->with('error', __('analysis.access_denied'));
        }

        $analysis = SeoAnalysis::findOrFail($id);

        // Decode analysis data JSON
        $analysisData = $analysis->analysis_data
            ? json_decode($analysis->analysis_data, true)
            : [];

        return view('guest.show', compact('analysis', 'analysisData'));
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        // Check daily limit
        $identifier = $this->getUserIdentifier($request);
        $limitCacheKey = "daily_analysis_limit:{$identifier}:" . now()->format('Y-m-d');
        $currentCount = Cache::get($limitCacheKey, 0);

        if ($currentCount >= 3) {
            return response()->json([
                'error' => true,
                'message' => __('analysis.daily_limit_exceeded'),
                'limit' => 3,
                'remaining' => 0,
                'reset_time' => now()->addDay()->startOfDay()->toISOString(),
                'action_required' => 'register'
            ], 429);
        }

        // Create analysis
        $analysis = SeoAnalysis::create([
            'url' => $request->url,
            'user_id' => null,
            'status' => 'pending'
        ]);

        // Store analysis ID for this guest
        $analysesCacheKey = "guest_analyses:{$identifier}";
        $guestAnalyses = Cache::get($analysesCacheKey, []);
        $guestAnalyses[] = $analysis->id;
        Cache::put($analysesCacheKey, $guestAnalyses, now()->addDays(7));

        // Increment daily counter
        $currentCount = Cache::get($limitCacheKey, 0) + 1;
        Cache::put($limitCacheKey, $currentCount, now()->addDay()->startOfDay());

        // Dispatch analysis job
        //\App\Jobs\CrawlUrl::dispatch($request->url, null, ['analysis_id' => $analysis->id]);

        AnalyzeUrl::dispatch($analysis->url, null);

        return response()->json([
            'success' => true,
            'analysis_id' => $analysis->id,
            'redirect_url' => route('guest.analyses.show', $analysis->id),
            'remaining' => max(0, 3 - ($currentCount + 1))
        ]);
    }

    private function getUserIdentifier(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
}
