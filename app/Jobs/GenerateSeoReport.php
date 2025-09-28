<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateSeoReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $tries = 2;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public array $analysisData,
        public ?int $userId = null
    ) {
        $this->onQueue('seo_reporting');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SEO report generation', [
            'url' => $this->url,
            'user_id' => $this->userId
        ]);

        try {
            $report = $this->generateReport();
            $score = $this->calculateSeoScore();

            // Generate different report formats
            $reportData = [
                'url' => $this->url,
                'generated_at' => now(),
                'user_id' => $this->userId,
                'seo_score' => $score,
                'report' => $report,
                'recommendations' => $this->generateRecommendations($report),
                'analysis_data' => $this->analysisData
            ];

            // Save report (in real implementation, save to database)
            $this->saveReport($reportData);

            // Generate exportable formats
            $this->generatePdfReport($reportData);
            $this->generateJsonReport($reportData);

            Log::info('SEO report generated successfully', [
                'url' => $this->url,
                'seo_score' => $score['overall'],
                'issues_found' => count($report['issues']),
                'warnings_found' => count($report['warnings'])
            ]);

        } catch (\Exception $e) {
            Log::error('SEO report generation failed', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate comprehensive SEO report
     */
    private function generateReport(): array
    {
        $issues = [];
        $warnings = [];
        $successes = [];

        // Extract seo_elements from the full analysis data
        $seoElements = $this->analysisData['seo_elements'] ?? $this->analysisData;

        // Analyze meta tags
        $meta = $seoElements['meta'] ?? [];

        if (empty($meta['title'] ?? null)) {
            $issues[] = __('analysis.report_missing_page_title');
        } elseif (($meta['title_length'] ?? 0) < 30 || ($meta['title_length'] ?? 0) > 60) {
            $warnings[] = __('analysis.report_page_title_length_issue', ['length' => ($meta['title_length'] ?? 0)]);
        } else {
            $successes[] = __('analysis.report_page_title_length_optimal');
        }

        if (empty($meta['description'] ?? null)) {
            $issues[] = __('analysis.report_missing_meta_description');
        } elseif (($meta['description_length'] ?? 0) < 120 || ($meta['description_length'] ?? 0) > 160) {
            $warnings[] = __('analysis.report_meta_description_length_issue', ['length' => ($meta['description_length'] ?? 0)]);
        } else {
            $successes[] = __('analysis.report_meta_description_length_optimal');
        }

        if (empty($meta['canonical'])) {
            $warnings[] = __('analysis.report_missing_canonical_url');
        } else {
            $successes[] = __('analysis.report_canonical_url_set');
        }

        // Analyze headings
        $headings = $seoElements['headings'] ?? [];
        if (empty($headings['h1'])) {
            $issues[] = __('analysis.report_missing_h1_tag');
        } elseif (count($headings['h1']) > 1) {
            $warnings[] = __('analysis.report_multiple_h1_tags');
        } else {
            $successes[] = __('analysis.report_single_h1_tag_found');
        }

        // Analyze images
        $images = $seoElements['images'] ?? [];
        if ($images['without_alt_count'] > 0) {
            $issues[] = __('analysis.report_images_missing_alt', ['count' => $images['without_alt_count']]);
        }

        if ($images['total_count'] > 0 && $images['without_alt_count'] === 0) {
            $successes[] = __('analysis.report_all_images_have_alt');
        }

        // Analyze links
        $links = $seoElements['links'] ?? [];
        if ($links['external_count'] > 0 && $links['nofollow_count'] === 0) {
            $warnings[] = __('analysis.report_consider_nofollow_external_links');
        }

        // Analyze content
        $content = $seoElements['content'] ?? [];
        if ($content['word_count'] < 300) {
            $warnings[] = __('analysis.report_content_too_short', ['count' => $content['word_count']]);
        } elseif ($content['word_count'] > 300) {
            $successes[] = __('analysis.report_content_length_adequate');
        }

        if ($content['text_to_html_ratio'] < 15) {
            $warnings[] = __('analysis.report_low_text_html_ratio', ['ratio' => $content['text_to_html_ratio']]);
        }

        // Analyze technical aspects - check from the main analysis data structure
        $technical = $seoElements['technical'] ?? [];
        $statusCode = $this->analysisData['status']['code'] ?? $technical['status_code'] ?? 200;

        if ($statusCode !== 200) {
            $issues[] = __('analysis.report_non_200_status_code', ['code' => $statusCode]);
        } else {
            $successes[] = __('analysis.report_page_returns_200_ok');
        }

        return [
            'issues' => $issues,
            'warnings' => $warnings,
            'successes' => $successes,
            'summary' => [
                'total_issues' => count($issues),
                'total_warnings' => count($warnings),
                'total_successes' => count($successes)
            ]
        ];
    }

    /**
     * Calculate overall SEO score
     */
    private function calculateSeoScore(): array
    {
        $seoElements = $this->analysisData['seo_elements'] ?? $this->analysisData;
        $meta = $seoElements['meta'] ?? [];
        $headings = $seoElements['headings'] ?? [];
        $images = $seoElements['images'] ?? [];
        $content = $seoElements['content'] ?? [];
        $technical = $seoElements['technical'] ?? [];

        $scores = [];

        // Meta score (30% weight)
        $metaScore = 0;
        if (!empty($meta['title'] ?? null) && ($meta['title_length'] ?? 0) >= 30 && ($meta['title_length'] ?? 0) <= 60) $metaScore += 40;
        if (!empty($meta['description'] ?? null) && ($meta['description_length'] ?? 0) >= 120 && ($meta['description_length'] ?? 0) <= 160) $metaScore += 40;
        if (!empty($meta['canonical'] ?? null)) $metaScore += 20;
        $scores['meta'] = $metaScore;

        // Content score (25% weight)
        $contentScore = 0;
        if (($content['word_count'] ?? 0) >= 300) $contentScore += 40;
        if (($content['text_to_html_ratio'] ?? 0) >= 15) $contentScore += 30;
        if (!empty($headings['h1'] ?? []) && count($headings['h1']) === 1) $contentScore += 30;
        $scores['content'] = $contentScore;

        // Images score (15% weight)
        $imageScore = 100;
        if (($images['total_count'] ?? 0) > 0) {
            $imageScore = max(0, 100 - ((($images['without_alt_count'] ?? 0) / ($images['total_count'] ?? 1)) * 100));
        }
        $scores['images'] = $imageScore;

        // Technical score (20% weight)
        $technicalScore = 0;
        $statusCode = $this->analysisData['status']['code'] ?? $technical['status_code'] ?? 200;
        if ($statusCode === 200) $technicalScore += 100;
        $scores['technical'] = $technicalScore;

        // Performance score (10% weight)
        $performance = $seoElements['performance'] ?? [];
        $performanceScore = 100;
        $htmlSizeKb = $performance['html_size_kb'] ?? ($this->analysisData['crawl_data']['html_size'] ?? 0) / 1024;
        if ($htmlSizeKb > 500) $performanceScore -= 20;
        if ($htmlSizeKb > 1000) $performanceScore -= 30;
        $scores['performance'] = max(0, $performanceScore);

        // Calculate weighted overall score
        $overall = round(
            ($scores['meta'] * 0.30) +
            ($scores['content'] * 0.25) +
            ($scores['images'] * 0.15) +
            ($scores['technical'] * 0.20) +
            ($scores['performance'] * 0.10)
        );

        $scores['overall'] = $overall;

        return $scores;
    }

    /**
     * Generate recommendations based on analysis
     */
    private function generateRecommendations(array $report): array
    {
        $recommendations = [];

        foreach ($report['issues'] as $issue) {
            if (str_contains($issue, 'title')) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'meta',
                    'issue' => $issue,
                    'recommendation' => __('analysis.report_add_unique_descriptive_title')
                ];
            }

            if (str_contains($issue, 'description')) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'meta',
                    'issue' => $issue,
                    'recommendation' => __('analysis.report_add_compelling_meta_description')
                ];
            }

            if (str_contains($issue, 'H1')) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'content',
                    'issue' => $issue,
                    'recommendation' => __('analysis.report_add_single_descriptive_h1')
                ];
            }

            if (str_contains($issue, 'alt')) {
                $recommendations[] = [
                    'type' => 'important',
                    'category' => 'images',
                    'issue' => $issue,
                    'recommendation' => __('analysis.report_add_descriptive_alt_text')
                ];
            }
        }

        foreach ($report['warnings'] as $warning) {
            if (str_contains($warning, 'length')) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'category' => 'meta',
                    'issue' => $warning,
                    'recommendation' => __('analysis.report_optimize_length_improve_ctr')
                ];
            }

            if (str_contains($warning, 'content')) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'category' => 'content',
                    'issue' => $warning,
                    'recommendation' => __('analysis.report_add_more_valuable_content')
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Save report to storage
     */
    private function saveReport(array $reportData): void
    {
        $filename = 'seo-reports/' . md5($this->url . time()) . '.json';
        Storage::put($filename, json_encode($reportData, JSON_PRETTY_PRINT));

        Log::info('SEO report saved to storage', [
            'url' => $this->url,
            'filename' => $filename
        ]);
    }

    /**
     * Generate PDF report (placeholder implementation)
     */
    private function generatePdfReport(array $reportData): void
    {
        // In a real implementation, you would use a library like DomPDF or wkhtmltopdf
        $htmlContent = $this->generateHtmlReport($reportData);
        $filename = 'seo-reports/pdf/' . md5($this->url . time()) . '.html';
        Storage::put($filename, $htmlContent);

        Log::info('PDF report generated', [
            'url' => $this->url,
            'filename' => $filename
        ]);
    }

    /**
     * Generate JSON report
     */
    private function generateJsonReport(array $reportData): void
    {
        $filename = 'seo-reports/json/' . md5($this->url . time()) . '.json';
        Storage::put($filename, json_encode($reportData, JSON_PRETTY_PRINT));

        Log::info('JSON report generated', [
            'url' => $this->url,
            'filename' => $filename
        ]);
    }

    /**
     * Generate HTML report template
     */
    private function generateHtmlReport(array $reportData): string
    {
        $url = htmlspecialchars($this->url);
        $score = $reportData['seo_score']['overall'];
        $generatedAt = $reportData['generated_at']->format('Y-m-d H:i:s');

        $issuesList = '';
        foreach ($reportData['report']['issues'] as $issue) {
            $issuesList .= '<li class="issue">' . htmlspecialchars($issue) . '</li>';
        }

        $warningsList = '';
        foreach ($reportData['report']['warnings'] as $warning) {
            $warningsList .= '<li class="warning">' . htmlspecialchars($warning) . '</li>';
        }

        $successesList = '';
        foreach ($reportData['report']['successes'] as $success) {
            $successesList .= '<li class="success">' . htmlspecialchars($success) . '</li>';
        }

        return "<!DOCTYPE html>
<html>
<head>
    <title>" . __('analysis.report_seo_analysis_report') . " for {$url}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .score { font-size: 48px; font-weight: bold; color: " . ($score >= 80 ? '#4CAF50' : ($score >= 60 ? '#FF9800' : '#F44336')) . "; }
        .issue { color: #F44336; margin: 10px 0; }
        .warning { color: #FF9800; margin: 10px 0; }
        .success { color: #4CAF50; margin: 10px 0; }
        .section { margin: 30px 0; }
        .section h2 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        ul { list-style-type: none; padding: 0; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>" . __('analysis.report_seo_analysis_report') . "</h1>
        <p><strong>" . __('analysis.report_url') . ":</strong> {$url}</p>
        <p><strong>" . __('analysis.report_generated') . ":</strong> {$generatedAt}</p>
        <p><strong>" . __('analysis.report_overall_seo_score') . ":</strong> <span class='score'>{$score}/100</span></p>
    </div>

    <div class='section'>
        <h2>" . __('analysis.report_issues_critical') . "</h2>
        <ul>{$issuesList}</ul>
    </div>

    <div class='section'>
        <h2>" . __('analysis.report_warnings_improvements_needed') . "</h2>
        <ul>{$warningsList}</ul>
    </div>

    <div class='section'>
        <h2>" . __('analysis.report_whats_working_well') . "</h2>
        <ul>{$successesList}</ul>
    </div>
</body>
</html>";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateSeoReport job failed permanently', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['reporting', 'seo', 'url:' . parse_url($this->url, PHP_URL_HOST)];
    }
}