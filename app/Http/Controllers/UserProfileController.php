<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\SeoAnalysis;

class UserProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user statistics
        $totalAnalyses = SeoAnalysis::where('user_id', $user->id)->count();
        $completedAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $averageScore = SeoAnalysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('overall_score')
            ->avg('overall_score');
        
        // Calculate monthly usage (assuming we track this)
        $monthlyAnalyses = SeoAnalysis::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $monthlyLimit = 100; // Free plan limit
        $usagePercentage = $monthlyLimit > 0 ? ($monthlyAnalyses / $monthlyLimit) * 100 : 0;
        
        return view('dashboard.profile', compact(
            'user',
            'totalAnalyses',
            'completedAnalyses',
            'averageScore',
            'monthlyAnalyses',
            'monthlyLimit',
            'usagePercentage'
        ));
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'company' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'bio' => $request->bio,
        ]);

        return redirect()->route('user.profile')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.profile')->with('success', 'Password updated successfully.');
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'email_notifications' => ['boolean'],
            'weekly_reports' => ['boolean'],
            'marketing_emails' => ['boolean'],
        ]);

        $user->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'weekly_reports' => $request->boolean('weekly_reports'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ]);

        return redirect()->route('user.profile')->with('success', 'Notification preferences updated.');
    }

    /**
     * Export user data.
     */
    public function exportData()
    {
        $user = Auth::user();
        $analyses = SeoAnalysis::where('user_id', $user->id)->get();
        
        $exportData = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'analyses' => $analyses->map(function ($analysis) {
                return [
                    'url' => $analysis->url,
                    'status' => $analysis->status,
                    'overall_score' => $analysis->overall_score,
                    'technical_score' => $analysis->technical_score,
                    'content_score' => $analysis->content_score,
                    'performance_score' => $analysis->performance_score,
                    'accessibility_score' => $analysis->accessibility_score,
                    'created_at' => $analysis->created_at,
                    'analyzed_at' => $analysis->analyzed_at,
                ];
            }),
            'statistics' => [
                'total_analyses' => $analyses->count(),
                'completed_analyses' => $analyses->where('status', 'completed')->count(),
                'average_score' => $analyses->where('status', 'completed')->avg('overall_score'),
            ]
        ];

        $filename = 'seo_validator_data_' . $user->id . '_' . now()->format('Y-m-d') . '.json';
        
        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}