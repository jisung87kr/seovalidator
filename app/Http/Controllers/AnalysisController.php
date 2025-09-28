<?php

namespace App\Http\Controllers;

use App\Models\SeoAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Knp\Snappy\Pdf;

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

    public function exportPdf($id)
    {
        $user = Auth::user();

        $analysis = SeoAnalysis::where('user_id', $user->id)
                               ->where('id', $id)
                               ->where('status', 'completed')
                               ->firstOrFail();

        // Decode analysis data
        $analysisData = $analysis->analysis_data ? json_decode($analysis->analysis_data, true) : [];

        // Generate PDF using wkhtmltopdf with Korean font support
        try {
            // Create wkhtmltopdf instance with Korean font options
            $pdf = new Pdf('/usr/local/bin/wkhtmltopdf');

            // Set options for Korean character support
            $pdf->setOptions([
                'page-size' => 'A4',
                'orientation' => 'portrait',
                'encoding' => 'utf-8',
                'enable-local-file-access' => true,
                'margin-top' => '10mm',
                'margin-bottom' => '10mm',
                'margin-left' => '10mm',
                'margin-right' => '10mm'
            ]);

            // Render the view to HTML
            $html = view('pdf.analysis-report', compact('analysis', 'analysisData'))->render();

            // Generate PDF from HTML
            $pdfContent = $pdf->getOutputFromHtml($html);

            // Generate filename with URL domain and date
            $urlParts = parse_url($analysis->url);
            $domain = $urlParts['host'] ?? 'analysis';
            $date = $analysis->created_at->format('Y-m-d');
            $filename = "seo-report-{$domain}-{$date}.pdf";

            // Return PDF as download
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent)
            ]);

        } catch (\Exception $e) {
            // Log error and provide user-friendly message
            \Log::error('PDF generation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'PDF 생성에 실패했습니다. 다시 시도해주세요.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportComparisonPdf(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'analysis1' => 'required|integer',
            'analysis2' => 'required|integer',
        ]);

        $analysis1 = SeoAnalysis::where('user_id', $user->id)
            ->where('id', $request->analysis1)
            ->where('status', 'completed')
            ->firstOrFail();

        $analysis2 = SeoAnalysis::where('user_id', $user->id)
            ->where('id', $request->analysis2)
            ->where('status', 'completed')
            ->firstOrFail();

        $comparison = [
            'analysis1' => $analysis1,
            'analysis2' => $analysis2,
            'data1' => $analysis1->analysis_data ? json_decode($analysis1->analysis_data, true) : [],
            'data2' => $analysis2->analysis_data ? json_decode($analysis2->analysis_data, true) : []
        ];

        // Generate PDF using wkhtmltopdf with Korean font support
        try {
            // Create wkhtmltopdf instance with Korean font options
            $pdf = new Pdf('/usr/local/bin/wkhtmltopdf');

            // Set options for Korean character support
            $pdf->setOptions([
                'page-size' => 'A4',
                'orientation' => 'portrait',
                'encoding' => 'utf-8',
                'enable-local-file-access' => true,
                'margin-top' => '10mm',
                'margin-bottom' => '10mm',
                'margin-left' => '10mm',
                'margin-right' => '10mm'
            ]);

            // Render the view to HTML
            $html = view('pdf.comparison-report', compact('comparison'))->render();

            // Generate PDF from HTML
            $pdfContent = $pdf->getOutputFromHtml($html);

            $date = now()->format('Y-m-d');
            $filename = "seo-comparison-report-{$date}.pdf";

            // Return PDF as download
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent)
            ]);

        } catch (\Exception $e) {
            // Log error and provide user-friendly message
            \Log::error('Comparison PDF generation failed: ' . $e->getMessage());

            return response()->json([
                'error' => '비교 리포트 PDF 생성에 실패했습니다. 다시 시도해주세요.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
