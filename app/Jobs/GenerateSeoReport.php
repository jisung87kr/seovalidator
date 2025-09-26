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

        // Analyze meta tags
        $meta = $this->analysisData['meta'];

        if (empty($meta['title'])) {
            $issues[] = 'Missing page title';
        } elseif ($meta['title_length'] < 30 || $meta['title_length'] > 60) {
            $warnings[] = "Page title length ({$meta['title_length']} chars) should be between 30-60 characters";
        } else {
            $successes[] = 'Page title length is optimal';
        }

        if (empty($meta['description'])) {
            $issues[] = 'Missing meta description';
        } elseif ($meta['description_length'] < 120 || $meta['description_length'] > 160) {
            $warnings[] = "Meta description length ({$meta['description_length']} chars) should be between 120-160 characters";
        } else {
            $successes[] = 'Meta description length is optimal';
        }

        if (empty($meta['canonical'])) {
            $warnings[] = 'Missing canonical URL';
        } else {
            $successes[] = 'Canonical URL is set';
        }

        // Analyze headings
        $headings = $this->analysisData['headings'];
        if (empty($headings['h1'])) {
            $issues[] = 'Missing H1 tag';
        } elseif (count($headings['h1']) > 1) {
            $warnings[] = 'Multiple H1 tags found - should have only one per page';
        } else {
            $successes[] = 'Single H1 tag found';
        }

        // Analyze images
        $images = $this->analysisData['images'];
        if ($images['without_alt_count'] > 0) {
            $issues[] = "{$images['without_alt_count']} images missing alt attributes";
        }

        if ($images['total_count'] > 0 && $images['without_alt_count'] === 0) {
            $successes[] = 'All images have alt attributes';
        }

        // Analyze links
        $links = $this->analysisData['links'];
        if ($links['external_count'] > 0 && $links['nofollow_count'] === 0) {
            $warnings[] = 'Consider adding rel="nofollow" to some external links';
        }

        // Analyze content
        $content = $this->analysisData['content'];
        if ($content['word_count'] < 300) {
            $warnings[] = "Content is quite short ({$content['word_count']} words) - consider adding more content";
        } elseif ($content['word_count'] > 300) {
            $successes[] = 'Content length is adequate';
        }

        if ($content['text_to_html_ratio'] < 15) {
            $warnings[] = "Text to HTML ratio is low ({$content['text_to_html_ratio']}%) - too much markup relative to content";
        }

        // Analyze technical aspects
        $technical = $this->analysisData['technical'];
        if ($technical['status_code'] !== 200) {
            $issues[] = "Non-200 status code: {$technical['status_code']}";
        } else {
            $successes[] = 'Page returns 200 OK status';
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
        $meta = $this->analysisData['meta'];
        $headings = $this->analysisData['headings'];
        $images = $this->analysisData['images'];
        $content = $this->analysisData['content'];
        $technical = $this->analysisData['technical'];

        $scores = [];

        // Meta score (30% weight)
        $metaScore = 0;
        if (!empty($meta['title']) && $meta['title_length'] >= 30 && $meta['title_length'] <= 60) $metaScore += 40;
        if (!empty($meta['description']) && $meta['description_length'] >= 120 && $meta['description_length'] <= 160) $metaScore += 40;
        if (!empty($meta['canonical'])) $metaScore += 20;
        $scores['meta'] = $metaScore;

        // Content score (25% weight)
        $contentScore = 0;
        if ($content['word_count'] >= 300) $contentScore += 40;
        if ($content['text_to_html_ratio'] >= 15) $contentScore += 30;
        if (!empty($headings['h1']) && count($headings['h1']) === 1) $contentScore += 30;
        $scores['content'] = $contentScore;

        // Images score (15% weight)
        $imageScore = 100;
        if ($images['total_count'] > 0) {
            $imageScore = max(0, 100 - (($images['without_alt_count'] / $images['total_count']) * 100));
        }
        $scores['images'] = $imageScore;

        // Technical score (20% weight)
        $technicalScore = 0;
        if ($technical['status_code'] === 200) $technicalScore += 100;
        $scores['technical'] = $technicalScore;

        // Performance score (10% weight)
        $performance = $this->analysisData['performance'];
        $performanceScore = 100;
        if ($performance['html_size_kb'] > 500) $performanceScore -= 20;
        if ($performance['html_size_kb'] > 1000) $performanceScore -= 30;
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
                    'recommendation' => 'Add a unique, descriptive title tag that accurately describes the page content'
                ];
            }

            if (str_contains($issue, 'description')) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'meta',
                    'issue' => $issue,
                    'recommendation' => 'Add a compelling meta description that summarizes the page content in 120-160 characters'
                ];
            }

            if (str_contains($issue, 'H1')) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'content',
                    'issue' => $issue,
                    'recommendation' => 'Add a single, descriptive H1 tag that includes your target keywords'
                ];
            }

            if (str_contains($issue, 'alt')) {
                $recommendations[] = [
                    'type' => 'important',
                    'category' => 'images',
                    'issue' => $issue,
                    'recommendation' => 'Add descriptive alt text to all images for better accessibility and SEO'
                ];
            }
        }

        foreach ($report['warnings'] as $warning) {
            if (str_contains($warning, 'length')) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'category' => 'meta',
                    'issue' => $warning,
                    'recommendation' => 'Optimize the length to improve search engine display and click-through rates'
                ];
            }

            if (str_contains($warning, 'content')) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'category' => 'content',
                    'issue' => $warning,
                    'recommendation' => 'Add more valuable, relevant content to improve user experience and SEO'
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
    <title>SEO Report for {$url}</title>
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
        <h1>SEO Analysis Report</h1>
        <p><strong>URL:</strong> {$url}</p>
        <p><strong>Generated:</strong> {$generatedAt}</p>
        <p><strong>Overall SEO Score:</strong> <span class='score'>{$score}/100</span></p>
    </div>

    <div class='section'>
        <h2>Issues (Critical)</h2>
        <ul>{$issuesList}</ul>
    </div>

    <div class='section'>
        <h2>Warnings (Improvements Needed)</h2>
        <ul>{$warningsList}</ul>
    </div>

    <div class='section'>
        <h2>What's Working Well</h2>
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