<?php

namespace App\Http\Controllers;

use App\Models\SeoAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user's recent analyses
        $recentAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate statistics
        $totalAnalyses = SeoAnalysis::where('user_id', $user->id)->count();
        $completedAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $averageScore = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        // Get score distribution
        $scoreDistribution = [
            'excellent' => SeoAnalysis::where('user_id', $user->id)
                ->where('overall_score', '>=', 90)->count(),
            'good' => SeoAnalysis::where('user_id', $user->id)
                ->whereBetween('overall_score', [70, 89])->count(),
            'fair' => SeoAnalysis::where('user_id', $user->id)
                ->whereBetween('overall_score', [50, 69])->count(),
            'poor' => SeoAnalysis::where('user_id', $user->id)
                ->where('overall_score', '<', 50)->count(),
        ];

        // Get recent activity
        $recentActivity = SeoAnalysis::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['url', 'status', 'overall_score', 'created_at']);

        return view('dashboard', compact(
            'recentAnalyses',
            'totalAnalyses',
            'completedAnalyses',
            'averageScore',
            'scoreDistribution',
            'recentActivity'
        ));
    }
}
