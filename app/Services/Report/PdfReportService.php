<?php

namespace App\Services\Report;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

/**
 * PDF Report Generation Service
 *
 * Handles PDF report generation using DomPDF with customizable templates,
 * branding, and professional formatting for SEO analysis reports.
 */
class PdfReportService
{
    private Dompdf $dompdf;
    private array $defaultOptions;

    public function __construct()
    {
        $this->initializeDompdf();
        $this->defaultOptions = [
            'orientation' => 'portrait',
            'paper_size' => 'A4',
            'include_header' => true,
            'include_footer' => true,
            'branding' => true,
            'charts' => true
        ];
    }

    /**
     * Generate PDF report for single URL
     */
    public function generateReport(array $reportData, string $template = 'standard'): string
    {
        $reportId = $reportData['report_id'];
        $url = $reportData['url'];

        Log::info('Generating PDF report', [
            'report_id' => $reportId,
            'url' => $url,
            'template' => $template
        ]);

        try {
            // Generate HTML content from template
            $htmlContent = $this->generateHtmlContent($reportData, $template);

            // Configure DomPDF for this report
            $this->configureDompdf($this->defaultOptions);

            // Load HTML and generate PDF
            $this->dompdf->loadHtml($htmlContent);
            $this->dompdf->render();

            // Save PDF to storage
            $filename = "reports/pdf/{$reportId}.pdf";
            $pdfContent = $this->dompdf->output();
            Storage::put($filename, $pdfContent);

            Log::info('PDF report generated successfully', [
                'report_id' => $reportId,
                'filename' => $filename,
                'file_size' => strlen($pdfContent)
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate combined PDF report for multiple URLs
     */
    public function generateCombinedReport(array $combinedData): string
    {
        $batchId = $combinedData['batch_id'];
        $reports = $combinedData['reports'];

        Log::info('Generating combined PDF report', [
            'batch_id' => $batchId,
            'report_count' => count($reports)
        ]);

        try {
            // Generate HTML for combined report
            $htmlContent = $this->generateCombinedHtmlContent($combinedData);

            // Configure DomPDF for combined report
            $this->configureDompdf(array_merge($this->defaultOptions, [
                'orientation' => 'portrait'
            ]));

            // Load HTML and generate PDF
            $this->dompdf->loadHtml($htmlContent);
            $this->dompdf->render();

            // Save combined PDF to storage
            $filename = "reports/pdf/combined_{$batchId}.pdf";
            $pdfContent = $this->dompdf->output();
            Storage::put($filename, $pdfContent);

            Log::info('Combined PDF report generated successfully', [
                'batch_id' => $batchId,
                'filename' => $filename,
                'file_size' => strlen($pdfContent)
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Combined PDF generation failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Initialize DomPDF with default configuration
     */
    private function initializeDompdf(): void
    {
        $options = new Options();
        $options->setDefaultFont('Arial');
        $options->setIsRemoteEnabled(true);
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsFontSubsettingEnabled(true);
        $options->setDefaultMediaType('print');
        $options->setIsPhpEnabled(false); // Security: disable PHP in templates

        $this->dompdf = new Dompdf($options);
    }

    /**
     * Configure DomPDF for specific report options
     */
    private function configureDompdf(array $options): void
    {
        $this->dompdf->setPaper($options['paper_size'] ?? 'A4', $options['orientation'] ?? 'portrait');
    }

    /**
     * Generate HTML content from template
     */
    private function generateHtmlContent(array $reportData, string $template): string
    {
        switch ($template) {
            case 'executive':
                return $this->generateExecutiveTemplate($reportData);
            case 'detailed':
                return $this->generateDetailedTemplate($reportData);
            case 'branded':
                return $this->generateBrandedTemplate($reportData);
            default:
                return $this->generateStandardTemplate($reportData);
        }
    }

    /**
     * Generate standard PDF template
     */
    private function generateStandardTemplate(array $reportData): string
    {
        $url = htmlspecialchars($reportData['url']);
        $score = $reportData['seo_score']['overall'] ?? 0;
        $generatedAt = $reportData['generated_at']->format('F j, Y \a\t g:i A');
        $summary = $reportData['summary'];

        $scoreColor = $this->getScoreColor($score);
        $grade = $summary['grade'] ?? 'N/A';

        // Build sections
        $issuesHtml = $this->buildIssuesSection($summary['issues'] ?? []);
        $warningsHtml = $this->buildWarningsSection($summary['warnings'] ?? []);
        $successesHtml = $this->buildSuccessesSection($summary['successes'] ?? []);
        $recommendationsHtml = $this->buildRecommendationsSection($reportData['recommendations'] ?? []);
        $detailsHtml = $this->buildDetailsSection($reportData);
        $insightsHtml = $this->buildInsightsSection($summary['key_insights'] ?? []);

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>SEO Analysis Report - {$url}</title>
    <style>
        {$this->getBaseStyles()}
        {$this->getReportStyles()}
    </style>
</head>
<body>
    <div class='header'>
        <div class='logo'>
            <h1>SEO Validator</h1>
            <p class='tagline'>Professional SEO Analysis Report</p>
        </div>
        <div class='report-info'>
            <p><strong>Generated:</strong> {$generatedAt}</p>
            <p><strong>Report ID:</strong> {$reportData['report_id']}</p>
        </div>
    </div>

    <div class='title-section'>
        <h1>SEO Analysis Report</h1>
        <h2 class='url'>{$url}</h2>
        <div class='score-card'>
            <div class='score-circle' style='border-color: {$scoreColor}; color: {$scoreColor}'>
                <span class='score-number'>{$score}</span>
                <span class='score-label'>SEO Score</span>
            </div>
            <div class='grade-info'>
                <h3>Grade: {$grade}</h3>
                <p class='score-description'>{$this->getScoreDescription($score)}</p>
            </div>
        </div>
    </div>

    <div class='executive-summary'>
        <h2>Executive Summary</h2>
        <div class='summary-stats'>
            <div class='stat-item critical'>
                <span class='stat-number'>{$summary['total_issues']}</span>
                <span class='stat-label'>Critical Issues</span>
            </div>
            <div class='stat-item warning'>
                <span class='stat-number'>{$summary['total_warnings']}</span>
                <span class='stat-label'>Warnings</span>
            </div>
            <div class='stat-item success'>
                <span class='stat-number'>{$summary['total_successes']}</span>
                <span class='stat-label'>Optimizations</span>
            </div>
        </div>
    </div>

    {$insightsHtml}

    <div class='page-break'></div>

    {$issuesHtml}

    {$warningsHtml}

    {$successesHtml}

    <div class='page-break'></div>

    {$recommendationsHtml}

    <div class='page-break'></div>

    {$detailsHtml}

    <div class='footer'>
        <p>Generated by SEO Validator | Professional SEO Analysis Tool</p>
        <p>For more information, visit: https://seovalidator.com</p>
    </div>
</body>
</html>";
    }

    /**
     * Generate executive summary template
     */
    private function generateExecutiveTemplate(array $reportData): string
    {
        $url = htmlspecialchars($reportData['url']);
        $score = $reportData['seo_score']['overall'] ?? 0;
        $summary = $reportData['summary'];
        $scoreColor = $this->getScoreColor($score);

        // Focus on high-level insights and key metrics
        $keyInsights = array_slice($summary['key_insights'] ?? [], 0, 3);
        $topIssues = array_slice($summary['issues'] ?? [], 0, 5);
        $topRecommendations = array_slice($reportData['recommendations'] ?? [], 0, 3);

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Executive SEO Report - {$url}</title>
    <style>
        {$this->getBaseStyles()}
        {$this->getExecutiveStyles()}
    </style>
</head>
<body>
    <div class='exec-header'>
        <h1>Executive SEO Summary</h1>
        <h2>{$url}</h2>
        <div class='exec-score' style='color: {$scoreColor}'>
            <span class='score'>{$score}/100</span>
            <span class='grade'>{$summary['grade']}</span>
        </div>
    </div>

    <div class='exec-overview'>
        <h3>SEO Performance Overview</h3>
        <div class='metrics-grid'>
            <div class='metric critical'>
                <h4>{$summary['total_issues']}</h4>
                <p>Critical Issues</p>
            </div>
            <div class='metric warning'>
                <h4>{$summary['total_warnings']}</h4>
                <p>Improvement Areas</p>
            </div>
            <div class='metric success'>
                <h4>{$summary['total_successes']}</h4>
                <p>Strong Areas</p>
            </div>
        </div>
    </div>

    <div class='exec-insights'>
        <h3>Key Insights</h3>
        <ul class='insights-list'>
        " . implode('', array_map(fn($insight) => "<li>" . htmlspecialchars($insight) . "</li>", $keyInsights)) . "
        </ul>
    </div>

    <div class='exec-actions'>
        <h3>Priority Actions</h3>
        <ol class='actions-list'>
        " . implode('', array_map(fn($issue) => "<li>" . htmlspecialchars($issue) . "</li>", $topIssues)) . "
        </ol>
    </div>

    <div class='exec-footer'>
        <p>This executive summary provides a high-level overview of your SEO performance. For detailed analysis and implementation guidance, refer to the complete report.</p>
    </div>
</body>
</html>";
    }

    /**
     * Generate detailed technical template
     */
    private function generateDetailedTemplate(array $reportData): string
    {
        // This would include comprehensive technical details, charts, and metrics
        return $this->generateStandardTemplate($reportData) . $this->getDetailedTechnicalSection($reportData);
    }

    /**
     * Generate branded template with custom styling
     */
    private function generateBrandedTemplate(array $reportData): string
    {
        // Custom branded version with company colors and logo
        return str_replace(
            $this->getBaseStyles(),
            $this->getBaseStyles() . $this->getBrandedStyles(),
            $this->generateStandardTemplate($reportData)
        );
    }

    /**
     * Generate combined report HTML
     */
    private function generateCombinedHtmlContent(array $combinedData): string
    {
        $batchId = $combinedData['batch_id'];
        $reports = $combinedData['reports'];
        $summary = $combinedData['summary'];

        $reportsHtml = '';
        foreach ($reports as $index => $report) {
            if (isset($report['error'])) {
                $reportsHtml .= "<div class='report-error'><h3>{$report['url']}</h3><p>Error: {$report['error']}</p></div>";
                continue;
            }

            $score = $report['seo_score']['overall'] ?? 0;
            $scoreColor = $this->getScoreColor($score);

            $reportsHtml .= "
            <div class='combined-report-item'>
                <h3>{$report['url']}</h3>
                <div class='report-summary'>
                    <span class='score' style='color: {$scoreColor}'>{$score}/100</span>
                    <span class='issues'>{$report['summary']['total_issues']} issues</span>
                    <span class='warnings'>{$report['summary']['total_warnings']} warnings</span>
                </div>
                <div class='top-issues'>
                    <h4>Top Issues:</h4>
                    <ul>" . implode('', array_map(fn($issue) => "<li>" . htmlspecialchars($issue) . "</li>",
                        array_slice($report['summary']['issues'] ?? [], 0, 3))) . "</ul>
                </div>
            </div>";

            if (($index + 1) % 3 === 0) {
                $reportsHtml .= "<div class='page-break'></div>";
            }
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Combined SEO Report - Batch {$batchId}</title>
    <style>
        {$this->getBaseStyles()}
        {$this->getCombinedReportStyles()}
    </style>
</head>
<body>
    <div class='header'>
        <h1>Combined SEO Analysis Report</h1>
        <p><strong>Batch ID:</strong> {$batchId}</p>
        <p><strong>Generated:</strong> {$summary['generated_at']->format('F j, Y \a\t g:i A')}</p>
    </div>

    <div class='combined-summary'>
        <h2>Batch Summary</h2>
        <div class='summary-grid'>
            <div class='summary-item'>
                <h3>{$summary['total_urls']}</h3>
                <p>Total URLs Analyzed</p>
            </div>
            <div class='summary-item'>
                <h3>{$summary['successful_reports']}</h3>
                <p>Successful Reports</p>
            </div>
            <div class='summary-item'>
                <h3>{$summary['average_seo_score']}</h3>
                <p>Average SEO Score</p>
            </div>
            <div class='summary-item'>
                <h3>{$summary['total_issues_found']}</h3>
                <p>Total Issues Found</p>
            </div>
        </div>
    </div>

    <div class='reports-section'>
        <h2>Individual Reports</h2>
        {$reportsHtml}
    </div>

    <div class='footer'>
        <p>Generated by SEO Validator | Combined Report Analysis</p>
    </div>
</body>
</html>";
    }

    /**
     * Helper methods for HTML sections
     */
    private function buildIssuesSection(array $issues): string
    {
        if (empty($issues)) {
            return "<div class='section'><h2>Critical Issues</h2><p class='no-items success'>🎉 No critical issues found!</p></div>";
        }

        $itemsHtml = implode('', array_map(fn($issue) =>
            "<li class='issue-item'><span class='icon'>⚠️</span>" . htmlspecialchars($issue) . "</li>", $issues));

        return "
        <div class='section issues-section'>
            <h2>Critical Issues ({count($issues)})</h2>
            <p class='section-description'>These issues require immediate attention as they can significantly impact your SEO performance.</p>
            <ul class='items-list'>{$itemsHtml}</ul>
        </div>";
    }

    private function buildWarningsSection(array $warnings): string
    {
        if (empty($warnings)) {
            return "<div class='section'><h2>Warnings</h2><p class='no-items'>No warnings to report.</p></div>";
        }

        $itemsHtml = implode('', array_map(fn($warning) =>
            "<li class='warning-item'><span class='icon'>⚡</span>" . htmlspecialchars($warning) . "</li>", $warnings));

        return "
        <div class='section warnings-section'>
            <h2>Warnings ({count($warnings)})</h2>
            <p class='section-description'>These areas could be improved for better SEO performance.</p>
            <ul class='items-list'>{$itemsHtml}</ul>
        </div>";
    }

    private function buildSuccessesSection(array $successes): string
    {
        if (empty($successes)) {
            return "";
        }

        $itemsHtml = implode('', array_map(fn($success) =>
            "<li class='success-item'><span class='icon'>✅</span>" . htmlspecialchars($success) . "</li>", $successes));

        return "
        <div class='section successes-section'>
            <h2>What's Working Well ({count($successes)})</h2>
            <p class='section-description'>These SEO elements are properly optimized.</p>
            <ul class='items-list'>{$itemsHtml}</ul>
        </div>";
    }

    private function buildRecommendationsSection(array $recommendations): string
    {
        if (empty($recommendations)) {
            return "<div class='section'><h2>Recommendations</h2><p>No specific recommendations available.</p></div>";
        }

        $recHtml = '';
        $priority = ['critical', 'important', 'improvement'];

        foreach ($priority as $level) {
            $levelRecs = array_filter($recommendations, fn($rec) => ($rec['type'] ?? '') === $level);
            if (!empty($levelRecs)) {
                $levelTitle = ucfirst($level) . ' Priority';
                $itemsHtml = implode('', array_map(fn($rec) =>
                    "<li><strong>" . htmlspecialchars($rec['issue'] ?? '') . "</strong><br>" .
                    htmlspecialchars($rec['recommendation'] ?? '') . "</li>", $levelRecs));

                $recHtml .= "<div class='rec-category'><h3>{$levelTitle}</h3><ul>{$itemsHtml}</ul></div>";
            }
        }

        return "
        <div class='section recommendations-section'>
            <h2>Detailed Recommendations</h2>
            <p class='section-description'>Prioritized action items to improve your SEO performance.</p>
            {$recHtml}
        </div>";
    }

    private function buildDetailsSection(array $reportData): string
    {
        $scores = $reportData['seo_score'];
        $analysisData = $reportData['analysis_data'];

        $detailsHtml = '';

        // Title Analysis
        if (isset($scores['title'])) {
            $titleScore = $scores['title'];
            $detailsHtml .= "
            <div class='detail-category'>
                <h3>Title Optimization ({$titleScore['score']}/{$titleScore['max_score']})</h3>
                <div class='metrics'>";

            foreach ($titleScore['metrics'] ?? [] as $metric => $value) {
                $detailsHtml .= "<span class='metric'><strong>" . ucfirst(str_replace('_', ' ', $metric)) . ":</strong> {$value}</span>";
            }

            $detailsHtml .= "</div></div>";
        }

        // Content Analysis
        if (isset($scores['content'])) {
            $contentScore = $scores['content'];
            $detailsHtml .= "
            <div class='detail-category'>
                <h3>Content Quality ({$contentScore['score']}/{$contentScore['max_score']})</h3>
                <div class='metrics'>";

            foreach ($contentScore['metrics'] ?? [] as $metric => $value) {
                $detailsHtml .= "<span class='metric'><strong>" . ucfirst(str_replace('_', ' ', $metric)) . ":</strong> {$value}</span>";
            }

            $detailsHtml .= "</div></div>";
        }

        return "
        <div class='section details-section'>
            <h2>Detailed Analysis</h2>
            <p class='section-description'>In-depth breakdown of each SEO category and metrics.</p>
            {$detailsHtml}
        </div>";
    }

    private function buildInsightsSection(array $insights): string
    {
        if (empty($insights)) {
            return "";
        }

        $insightsHtml = implode('', array_map(fn($insight) =>
            "<li class='insight-item'><span class='icon'>💡</span>" . htmlspecialchars($insight) . "</li>", $insights));

        return "
        <div class='section insights-section'>
            <h2>Key Insights</h2>
            <p class='section-description'>Important observations about your SEO performance.</p>
            <ul class='insights-list'>{$insightsHtml}</ul>
        </div>";
    }

    /**
     * Utility methods
     */
    private function getScoreColor(int $score): string
    {
        if ($score >= 80) return '#4CAF50'; // Green
        if ($score >= 60) return '#FF9800'; // Orange
        return '#F44336'; // Red
    }

    private function getScoreDescription(int $score): string
    {
        if ($score >= 90) return 'Excellent SEO optimization';
        if ($score >= 80) return 'Good SEO foundation with room for improvement';
        if ($score >= 70) return 'Moderate SEO optimization';
        if ($score >= 60) return 'Basic SEO in place, significant improvements needed';
        if ($score >= 50) return 'Poor SEO optimization, critical issues present';
        return 'Significant SEO issues requiring immediate attention';
    }

    /**
     * CSS Styles
     */
    private function getBaseStyles(): string
    {
        return "
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.6; color: #333; }
        .page-break { page-break-before: always; }
        h1 { font-size: 24px; margin-bottom: 10px; color: #2c3e50; }
        h2 { font-size: 18px; margin-bottom: 8px; color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        h3 { font-size: 14px; margin-bottom: 6px; color: #2c3e50; }
        p { margin-bottom: 8px; }
        ul, ol { margin-left: 20px; margin-bottom: 10px; }
        li { margin-bottom: 4px; }
        .header { border-bottom: 3px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #3498db; }
        .tagline { color: #7f8c8d; font-style: italic; }
        .section { margin-bottom: 25px; padding: 15px; border-left: 4px solid #ecf0f1; }
        .no-items { color: #7f8c8d; font-style: italic; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #7f8c8d; border-top: 1px solid #ecf0f1; padding-top: 15px; }
        ";
    }

    private function getReportStyles(): string
    {
        return "
        .title-section { text-align: center; margin-bottom: 30px; }
        .url { color: #3498db; font-size: 16px; margin-bottom: 20px; }
        .score-card { display: flex; align-items: center; justify-content: center; gap: 30px; margin: 20px 0; }
        .score-circle { width: 120px; height: 120px; border: 8px solid; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .score-number { font-size: 36px; font-weight: bold; }
        .score-label { font-size: 12px; text-transform: uppercase; }
        .grade-info h3 { font-size: 24px; margin-bottom: 5px; }
        .score-description { color: #7f8c8d; max-width: 200px; }
        .executive-summary { background: #ecf0f1; padding: 20px; margin-bottom: 25px; border-radius: 5px; }
        .summary-stats { display: flex; justify-content: space-around; margin-top: 15px; }
        .stat-item { text-align: center; }
        .stat-number { display: block; font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 11px; text-transform: uppercase; color: #7f8c8d; }
        .critical .stat-number { color: #e74c3c; }
        .warning .stat-number { color: #f39c12; }
        .success .stat-number { color: #27ae60; }
        .items-list { list-style: none; }
        .issue-item, .warning-item, .success-item { padding: 8px; margin-bottom: 5px; border-radius: 3px; }
        .issue-item { background: #fdf2f2; border-left: 4px solid #e74c3c; }
        .warning-item { background: #fef9e7; border-left: 4px solid #f39c12; }
        .success-item { background: #f0f9f4; border-left: 4px solid #27ae60; }
        .icon { margin-right: 8px; }
        .section-description { color: #7f8c8d; margin-bottom: 15px; font-style: italic; }
        .rec-category { margin-bottom: 20px; }
        .rec-category h3 { color: #e74c3c; }
        .detail-category { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .metrics { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; }
        .metric { font-size: 11px; background: white; padding: 5px 8px; border-radius: 3px; }
        .insights-list { list-style: none; }
        .insight-item { padding: 10px; margin-bottom: 8px; background: #e8f4fd; border-left: 4px solid #3498db; border-radius: 3px; }
        ";
    }

    private function getExecutiveStyles(): string
    {
        return "
        .exec-header { text-align: center; margin-bottom: 40px; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .exec-score { margin-top: 20px; }
        .exec-score .score { font-size: 48px; font-weight: bold; display: block; }
        .exec-score .grade { font-size: 24px; }
        .exec-overview { margin-bottom: 30px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
        .metric { text-align: center; padding: 20px; border-radius: 8px; }
        .metric h4 { font-size: 28px; margin-bottom: 5px; }
        .metric.critical { background: #fef5f5; color: #e53e3e; }
        .metric.warning { background: #fffaf0; color: #dd6b20; }
        .metric.success { background: #f0fff4; color: #38a169; }
        .insights-list, .actions-list { padding-left: 0; }
        .insights-list li, .actions-list li { margin-bottom: 10px; padding: 8px; background: #f7fafc; border-radius: 4px; }
        .exec-footer { margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px; text-align: center; }
        ";
    }

    private function getCombinedReportStyles(): string
    {
        return "
        .combined-summary { background: #f8f9fa; padding: 25px; margin-bottom: 30px; border-radius: 8px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
        .summary-item { text-align: center; padding: 15px; background: white; border-radius: 5px; }
        .summary-item h3 { font-size: 24px; color: #3498db; margin-bottom: 5px; }
        .combined-report-item { margin-bottom: 25px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .combined-report-item h3 { color: #3498db; margin-bottom: 10px; }
        .report-summary { display: flex; gap: 20px; margin-bottom: 15px; align-items: center; }
        .report-summary .score { font-size: 18px; font-weight: bold; }
        .report-summary .issues { color: #e74c3c; }
        .report-summary .warnings { color: #f39c12; }
        .top-issues ul { margin-left: 20px; }
        .report-error { padding: 15px; background: #fef2f2; border: 1px solid #fed7d7; border-radius: 5px; margin-bottom: 15px; }
        ";
    }

    private function getBrandedStyles(): string
    {
        return "
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header h1 { color: white; }
        h2 { border-bottom-color: #667eea; }
        .score-circle { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        ";
    }

    private function getDetailedTechnicalSection(array $reportData): string
    {
        // Add comprehensive technical analysis section
        return "
        <div class='page-break'></div>
        <div class='section technical-section'>
            <h2>Technical SEO Analysis</h2>
            <p class='section-description'>Detailed technical performance metrics and recommendations.</p>
            <!-- Technical analysis details would go here -->
        </div>";
    }
}