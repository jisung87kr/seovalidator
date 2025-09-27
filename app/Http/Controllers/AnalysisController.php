<?php

namespace App\Http\Controllers;

use App\Models\SeoAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    public function history(Request $request)
    {
        $user = Auth::user();

        $query = SeoAnalysis::where('user_id', $user->id);

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where('url', 'like', '%' . $request->search . '%');
        }

        if ($request->has('score_range') && $request->score_range !== '') {
            switch ($request->score_range) {
                case 'excellent':
                    $query->where('overall_score', '>=', 90);
                    break;
                case 'good':
                    $query->whereBetween('overall_score', [70, 89]);
                    break;
                case 'fair':
                    $query->whereBetween('overall_score', [50, 69]);
                    break;
                case 'poor':
                    $query->where('overall_score', '<', 50);
                    break;
            }
        }

        // Sort by most recent first
        $analyses = $query->orderBy('created_at', 'desc')
                         ->paginate(20)
                         ->withQueryString();

        // Get statistics
        $totalAnalyses = SeoAnalysis::where('user_id', $user->id)->count();
        $completedAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')->count();
        $averageScore = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        return view('analysis.history', compact(
            'analyses',
            'totalAnalyses',
            'completedAnalyses',
            'averageScore'
        ));
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();

        $analysis = SeoAnalysis::where('user_id', $user->id)
                               ->where('id', $id)
                               ->firstOrFail();

        // Decode analysis data
        $analysisData = $analysis->analysis_data ? json_decode($analysis->analysis_data, true) : [];

        return view('analysis.show', compact('analysis', 'analysisData'));
    }

    public function compare(Request $request)
    {
        $user = Auth::user();

        // Get user's completed analyses for comparison selection
        $availableAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'url', 'overall_score', 'created_at']);

        $comparison = null;

        if ($request->has(['analysis1', 'analysis2'])) {
            $analysis1 = SeoAnalysis::where('user_id', $user->id)
                ->where('id', $request->analysis1)
                ->first();

            $analysis2 = SeoAnalysis::where('user_id', $user->id)
                ->where('id', $request->analysis2)
                ->first();

            if ($analysis1 && $analysis2) {
                $comparison = [
                    'analysis1' => $analysis1,
                    'analysis2' => $analysis2,
                    'data1' => $analysis1->analysis_data ? json_decode($analysis1->analysis_data, true) : [],
                    'data2' => $analysis2->analysis_data ? json_decode($analysis2->analysis_data, true) : []
                ];
            }
        }

        return view('analysis.compare', compact('availableAnalyses', 'comparison'));
    }
}
