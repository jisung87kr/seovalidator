<?php

namespace App\Services\Report;

use App\Services\Analysis\SeoMetrics;
use App\Services\Analysis\PerformanceAnalyzer;
use App\Services\Analysis\RecommendationEngine;
use App\Services\Crawler\PageAnalyzer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Main Report Generator Service
 *
 * Orchestrates the generation of comprehensive SEO reports in multiple formats.
 * Handles report creation, formatting, and delivery coordination.
 */
class ReportGeneratorService
{
    private SeoMetrics $seoMetrics;
    private PerformanceAnalyzer $performanceAnalyzer;
    private RecommendationEngine $recommendationEngine;
    private PdfReportService $pdfService;
    private ExcelReportService $excelService;
    private EmailReportService $emailService;

    public function __construct(
        SeoMetrics $seoMetrics,
        PerformanceAnalyzer $performanceAnalyzer,
        RecommendationEngine $recommendationEngine,
        PdfReportService $pdfService,
        ExcelReportService $excelService,
        EmailReportService $emailService
    ) {
        $this->seoMetrics = $seoMetrics;
        $this->performanceAnalyzer = $performanceAnalyzer;
        $this->recommendationEngine = $recommendationEngine;
        $this->pdfService = $pdfService;
        $this->excelService = $excelService;
        $this->emailService = $emailService;
    }

    /**
     * Generate comprehensive report for a single URL
     */
    public function generateReport(array $analysisData, array $options = []): array
    {
        $url = $analysisData['url'] ?? '';
        $userId = $options['user_id'] ?? null;
        $formats = $options['formats'] ?? ['pdf', 'excel', 'json'];
        $emailTo = $options['email_to'] ?? null;
        $templateType = $options['template'] ?? 'standard';

        Log::info('Starting comprehensive report generation', [
            'url' => $url,
            'user_id' => $userId,
            'formats' => $formats,
            'template' => $templateType
        ]);

        try {
            // Generate core report data
            $reportData = $this->buildReportData($analysisData, $options);

            // Store report metadata
            $reportId = $this->storeReportMetadata($reportData);
            $reportData['report_id'] = $reportId;

            $generatedFiles = [];

            // Generate requested formats
            foreach ($formats as $format) {
                switch ($format) {
                    case 'pdf':
                        $pdfPath = $this->pdfService->generateReport($reportData, $templateType);
                        $generatedFiles['pdf'] = $pdfPath;
                        break;

                    case 'excel':
                        $excelPath = $this->excelService->generateReport($reportData);
                        $generatedFiles['excel'] = $excelPath;
                        break;

                    case 'json':
                        $jsonPath = $this->generateJsonReport($reportData);
                        $generatedFiles['json'] = $jsonPath;
                        break;
                }
            }

            // Send email if requested
            if ($emailTo && !empty($generatedFiles)) {
                $this->emailService->sendReport($emailTo, $reportData, $generatedFiles);
            }

            $result = [
                'report_id' => $reportId,
                'url' => $url,
                'generated_at' => $reportData['generated_at'],
                'seo_score' => $reportData['seo_score'],
                'files' => $generatedFiles,
                'summary' => $reportData['summary']
            ];

            Log::info('Report generation completed successfully', [
                'report_id' => $reportId,
                'url' => $url,
                'formats_generated' => array_keys($generatedFiles),
                'seo_score' => $reportData['seo_score']['overall']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate bulk reports for multiple URLs
     */
    public function generateBulkReports(array $urlsData, array $options = []): array
    {
        $batchId = uniqid('batch_', true);
        $results = [];
        $formats = $options['formats'] ?? ['pdf'];
        $combineReports = $options['combine_reports'] ?? false;

        Log::info('Starting bulk report generation', [
            'batch_id' => $batchId,
            'url_count' => count($urlsData),
            'formats' => $formats,
            'combine_reports' => $combineReports
        ]);

        foreach ($urlsData as $index => $urlData) {
            try {
                $urlOptions = array_merge($options, [
                    'batch_id' => $batchId,
                    'batch_index' => $index + 1,
                    'batch_total' => count($urlsData)
                ]);

                $result = $this->generateReport($urlData, $urlOptions);
                $results[] = $result;

            } catch (\Exception $e) {
                Log::warning('Failed to generate report for URL in batch', [
                    'batch_id' => $batchId,
                    'url' => $urlData['url'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'url' => $urlData['url'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'generated_at' => now(),
                    'success' => false
                ];
            }
        }

        // Generate combined report if requested
        if ($combineReports && !empty($results)) {
            $combinedReport = $this->generateCombinedReport($results, $batchId, $options);
            $results['combined_report'] = $combinedReport;
        }

        Log::info('Bulk report generation completed', [
            'batch_id' => $batchId,
            'total_reports' => count($results),
            'successful_reports' => count(array_filter($results, fn($r) => $r['success'] ?? true))
        ]);

        return [
            'batch_id' => $batchId,
            'total_urls' => count($urlsData),
            'reports' => $results,
            'generated_at' => now()
        ];
    }

    /**
     * Generate scheduled report
     */
    public function generateScheduledReport(array $scheduleConfig): array
    {
        $scheduleId = $scheduleConfig['schedule_id'];
        $urls = $scheduleConfig['urls'];
        $formats = $scheduleConfig['formats'] ?? ['pdf'];
        $emailRecipients = $scheduleConfig['email_recipients'] ?? [];

        Log::info('Generating scheduled report', [
            'schedule_id' => $scheduleId,
            'url_count' => count($urls),
            'recipients' => count($emailRecipients)
        ]);

        $options = [
            'schedule_id' => $scheduleId,
            'formats' => $formats,
            'template' => $scheduleConfig['template'] ?? 'standard',
            'combine_reports' => $scheduleConfig['combine_reports'] ?? true
        ];

        // Generate reports for all URLs
        $bulkResult = $this->generateBulkReports($urls, $options);

        // Send to all email recipients
        if (!empty($emailRecipients)) {
            foreach ($emailRecipients as $recipient) {
                try {
                    $this->emailService->sendScheduledReport(
                        $recipient,
                        $bulkResult,
                        $scheduleConfig
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send scheduled report to recipient', [
                        'schedule_id' => $scheduleId,
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $bulkResult;
    }

    /**
     * Build comprehensive report data structure
     */
    private function buildReportData(array $analysisData, array $options): array
    {
        $url = $analysisData['url'];
        $timestamp = now();

        // Calculate SEO scores using SeoMetrics service
        $seoScore = $this->calculateComprehensiveSeoScore($analysisData);

        // Generate recommendations
        $recommendations = $this->recommendationEngine->generateRecommendations($analysisData, $seoScore);

        // Build comprehensive report structure
        $reportData = [
            'url' => $url,
            'generated_at' => $timestamp,
            'user_id' => $options['user_id'] ?? null,
            'batch_id' => $options['batch_id'] ?? null,
            'seo_score' => $seoScore,
            'analysis_data' => $analysisData,
            'recommendations' => $recommendations,
            'summary' => $this->generateReportSummary($analysisData, $seoScore),
            'metadata' => [
                'version' => '1.0',
                'generator' => 'SEOValidator Report Generator',
                'template' => $options['template'] ?? 'standard',
                'generation_time_ms' => 0 // Will be updated after generation
            ]
        ];

        return $reportData;
    }

    /**
     * Calculate comprehensive SEO score using SeoMetrics
     */
    private function calculateComprehensiveSeoScore(array $analysisData): array
    {
        $scores = [];

        // Title analysis
        if (isset($analysisData['meta']['title'])) {
            $titleData = [
                'title' => $analysisData['meta']['title'],
                'title_length' => $analysisData['meta']['title_length'] ?? strlen($analysisData['meta']['title'])
            ];
            $scores['title'] = $this->seoMetrics->calculateAdvancedTitleScore($titleData);
        }

        // Content analysis
        if (isset($analysisData['content'])) {
            $scores['content'] = $this->seoMetrics->calculateAdvancedContentScore($analysisData['content']);
        }

        // Technical performance
        if (isset($analysisData['technical'])) {
            $performanceData = $analysisData['performance'] ?? [];
            $scores['technical'] = $this->seoMetrics->calculateTechnicalPerformanceScore(
                $analysisData['technical'],
                $performanceData
            );
        }

        // Calculate weighted overall score
        $weights = $this->seoMetrics->getPrimaryWeights();
        $overallScore = 0;
        $totalWeight = 0;

        foreach ($scores as $category => $scoreData) {
            $weight = $weights[$category] ?? 0;
            $overallScore += ($scoreData['score'] / 100) * $weight;
            $totalWeight += $weight;
        }

        $scores['overall'] = $totalWeight > 0 ? round($overallScore) : 0;
        $scores['calculated_at'] = now()->toISOString();

        return $scores;
    }

    /**
     * Generate report summary
     */
    private function generateReportSummary(array $analysisData, array $seoScore): array
    {
        $issues = [];
        $warnings = [];
        $successes = [];

        // Collect issues from all score categories
        foreach ($seoScore as $category => $scoreData) {
            if (is_array($scoreData) && isset($scoreData['issues'])) {
                $issues = array_merge($issues, $scoreData['issues']);
            }
            if (is_array($scoreData) && isset($scoreData['recommendations'])) {
                foreach ($scoreData['recommendations'] as $rec) {
                    if (str_contains(strtolower($rec), 'critical') || str_contains(strtolower($rec), 'missing')) {
                        $issues[] = $rec;
                    } else {
                        $warnings[] = $rec;
                    }
                }
            }
        }

        // Generate successes based on good scores
        foreach ($seoScore as $category => $scoreData) {
            if (is_array($scoreData) && ($scoreData['score'] ?? 0) >= 80) {
                $successes[] = ucfirst($category) . " optimization is excellent";
            }
        }

        return [
            'overall_score' => $seoScore['overall'] ?? 0,
            'total_issues' => count($issues),
            'total_warnings' => count($warnings),
            'total_successes' => count($successes),
            'issues' => $issues,
            'warnings' => $warnings,
            'successes' => $successes,
            'grade' => $this->calculateGrade($seoScore['overall'] ?? 0),
            'key_insights' => $this->generateKeyInsights($analysisData, $seoScore)
        ];
    }

    /**
     * Calculate letter grade from score
     */
    private function calculateGrade(int $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Generate key insights
     */
    private function generateKeyInsights(array $analysisData, array $seoScore): array
    {
        $insights = [];

        // Performance insight
        if (isset($analysisData['performance']['load_time'])) {
            $loadTime = $analysisData['performance']['load_time'];
            if ($loadTime > 3) {
                $insights[] = "Page load time of {$loadTime}s may impact user experience and rankings";
            } elseif ($loadTime <= 2) {
                $insights[] = "Excellent page load time of {$loadTime}s provides great user experience";
            }
        }

        // Content insight
        if (isset($analysisData['content']['word_count'])) {
            $wordCount = $analysisData['content']['word_count'];
            if ($wordCount < 300) {
                $insights[] = "Content is quite short - consider expanding for better SEO value";
            } elseif ($wordCount > 1000) {
                $insights[] = "Comprehensive content length provides good SEO foundation";
            }
        }

        // Mobile optimization insight
        if (isset($analysisData['technical']['mobile_friendly'])) {
            if (!$analysisData['technical']['mobile_friendly']) {
                $insights[] = "Mobile optimization is critical for modern SEO success";
            }
        }

        return array_slice($insights, 0, 5); // Limit to top 5 insights
    }

    /**
     * Store report metadata for tracking
     */
    private function storeReportMetadata(array $reportData): string
    {
        $reportId = uniqid('report_', true);

        $metadata = [
            'report_id' => $reportId,
            'url' => $reportData['url'],
            'generated_at' => $reportData['generated_at']->toISOString(),
            'user_id' => $reportData['user_id'],
            'batch_id' => $reportData['batch_id'],
            'seo_score' => $reportData['seo_score']['overall'] ?? 0,
            'total_issues' => count($reportData['summary']['issues'] ?? []),
            'total_warnings' => count($reportData['summary']['warnings'] ?? [])
        ];

        $filename = "reports/metadata/{$reportId}.json";
        Storage::put($filename, json_encode($metadata, JSON_PRETTY_PRINT));

        return $reportId;
    }

    /**
     * Generate JSON report
     */
    private function generateJsonReport(array $reportData): string
    {
        $reportId = $reportData['report_id'];
        $filename = "reports/json/{$reportId}.json";

        Storage::put($filename, json_encode($reportData, JSON_PRETTY_PRINT));

        Log::info('JSON report generated', [
            'report_id' => $reportId,
            'url' => $reportData['url'],
            'filename' => $filename
        ]);

        return $filename;
    }

    /**
     * Generate combined report for bulk operations
     */
    private function generateCombinedReport(array $reports, string $batchId, array $options): array
    {
        $formats = $options['formats'] ?? ['pdf'];
        $combinedFiles = [];

        // Calculate aggregate statistics
        $totalScore = 0;
        $totalIssues = 0;
        $successfulReports = array_filter($reports, fn($r) => $r['success'] ?? true);

        foreach ($successfulReports as $report) {
            $totalScore += $report['seo_score']['overall'] ?? 0;
            $totalIssues += $report['summary']['total_issues'] ?? 0;
        }

        $avgScore = count($successfulReports) > 0 ? round($totalScore / count($successfulReports)) : 0;

        $combinedData = [
            'batch_id' => $batchId,
            'reports' => $reports,
            'summary' => [
                'total_urls' => count($reports),
                'successful_reports' => count($successfulReports),
                'average_seo_score' => $avgScore,
                'total_issues_found' => $totalIssues,
                'generated_at' => now()
            ]
        ];

        // Generate combined reports in requested formats
        foreach ($formats as $format) {
            switch ($format) {
                case 'pdf':
                    $pdfPath = $this->pdfService->generateCombinedReport($combinedData);
                    $combinedFiles['pdf'] = $pdfPath;
                    break;

                case 'excel':
                    $excelPath = $this->excelService->generateCombinedReport($combinedData);
                    $combinedFiles['excel'] = $excelPath;
                    break;
            }
        }

        return [
            'batch_id' => $batchId,
            'files' => $combinedFiles,
            'summary' => $combinedData['summary']
        ];
    }

    /**
     * Get report history for a user
     */
    public function getReportHistory(int $userId, int $limit = 50): array
    {
        // In a real implementation, this would query a database
        // For now, we'll read from stored metadata files
        $metadataFiles = Storage::files('reports/metadata');
        $reports = [];

        foreach ($metadataFiles as $file) {
            $metadata = json_decode(Storage::get($file), true);
            if ($metadata && ($metadata['user_id'] ?? null) == $userId) {
                $reports[] = $metadata;
            }
        }

        // Sort by generation date (newest first)
        usort($reports, fn($a, $b) => strtotime($b['generated_at']) - strtotime($a['generated_at']));

        return array_slice($reports, 0, $limit);
    }

    /**
     * Delete old reports (cleanup)
     */
    public function cleanupOldReports(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deletedCount = 0;

        $metadataFiles = Storage::files('reports/metadata');

        foreach ($metadataFiles as $file) {
            $metadata = json_decode(Storage::get($file), true);
            if ($metadata && isset($metadata['generated_at'])) {
                $generatedAt = Carbon::parse($metadata['generated_at']);

                if ($generatedAt->lt($cutoffDate)) {
                    $reportId = $metadata['report_id'];

                    // Delete all associated files
                    Storage::delete("reports/json/{$reportId}.json");
                    Storage::delete("reports/pdf/{$reportId}.pdf");
                    Storage::delete("reports/excel/{$reportId}.xlsx");
                    Storage::delete($file);

                    $deletedCount++;
                }
            }
        }

        Log::info('Completed report cleanup', [
            'cutoff_date' => $cutoffDate->toISOString(),
            'deleted_count' => $deletedCount
        ]);

        return $deletedCount;
    }
}