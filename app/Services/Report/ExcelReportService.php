<?php

namespace App\Services\Report;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Excel Report Generation Service
 *
 * Handles Excel export generation with multiple sheets, formatting,
 * and comprehensive data analysis for SEO reports.
 */
class ExcelReportService
{
    /**
     * Generate Excel report for single URL
     */
    public function generateReport(array $reportData): string
    {
        $reportId = $reportData['report_id'];
        $url = $reportData['url'];

        Log::info('Generating Excel report', [
            'report_id' => $reportId,
            'url' => $url
        ]);

        try {
            $export = new SeoReportExport($reportData);
            $filename = "reports/excel/{$reportId}.xlsx";

            Excel::store($export, $filename, 'local');

            Log::info('Excel report generated successfully', [
                'report_id' => $reportId,
                'filename' => $filename
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Excel generation failed', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate combined Excel report for multiple URLs
     */
    public function generateCombinedReport(array $combinedData): string
    {
        $batchId = $combinedData['batch_id'];

        Log::info('Generating combined Excel report', [
            'batch_id' => $batchId,
            'report_count' => count($combinedData['reports'])
        ]);

        try {
            $export = new CombinedSeoReportExport($combinedData);
            $filename = "reports/excel/combined_{$batchId}.xlsx";

            Excel::store($export, $filename, 'local');

            Log::info('Combined Excel report generated successfully', [
                'batch_id' => $batchId,
                'filename' => $filename
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Combined Excel generation failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

/**
 * Single SEO Report Excel Export
 */
class SeoReportExport implements WithMultipleSheets
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new SummarySheet($this->reportData),
            'SEO Scores' => new ScoresSheet($this->reportData),
            'Issues & Recommendations' => new IssuesSheet($this->reportData),
            'Technical Analysis' => new TechnicalSheet($this->reportData),
            'Content Analysis' => new ContentSheet($this->reportData),
            'Meta Tags' => new MetaTagsSheet($this->reportData),
            'Raw Data' => new RawDataSheet($this->reportData)
        ];
    }
}

/**
 * Combined Reports Excel Export
 */
class CombinedSeoReportExport implements WithMultipleSheets
{
    private array $combinedData;

    public function __construct(array $combinedData)
    {
        $this->combinedData = $combinedData;
    }

    public function sheets(): array
    {
        return [
            'Batch Summary' => new BatchSummarySheet($this->combinedData),
            'All Scores' => new AllScoresSheet($this->combinedData),
            'Issues Overview' => new IssuesOverviewSheet($this->combinedData),
            'Detailed Reports' => new DetailedReportsSheet($this->combinedData)
        ];
    }
}

/**
 * Summary Sheet - Overview of SEO performance
 */
class SummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $summary = $this->reportData['summary'];
        $scores = $this->reportData['seo_score'];

        return collect([
            ['URL', $this->reportData['url']],
            ['Generated At', $this->reportData['generated_at']->format('Y-m-d H:i:s')],
            ['Report ID', $this->reportData['report_id']],
            [''],
            ['Overall SEO Score', $scores['overall'] ?? 0],
            ['Grade', $summary['grade'] ?? 'N/A'],
            [''],
            ['Critical Issues', $summary['total_issues']],
            ['Warnings', $summary['total_warnings']],
            ['Optimizations', $summary['total_successes']],
            [''],
            ['Performance Summary', ''],
            ['Title Score', $scores['title']['score'] ?? 'N/A'],
            ['Content Score', $scores['content']['score'] ?? 'N/A'],
            ['Technical Score', $scores['technical']['score'] ?? 'N/A'],
            [''],
            ['Key Insights', ''],
            ...array_map(fn($insight) => ['', $insight], $summary['key_insights'] ?? [])
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            'A:A' => ['font' => ['bold' => true]],
            'A5:B5' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']]],
            'A8:B10' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF3E0']]],
            'A12:B14' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E8']]]
        ];
    }
}

/**
 * Scores Sheet - Detailed SEO scores breakdown
 */
class ScoresSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $scores = $this->reportData['seo_score'];
        $data = [];

        foreach ($scores as $category => $scoreData) {
            if ($category === 'overall' || $category === 'calculated_at') {
                continue;
            }

            if (is_array($scoreData)) {
                $data[] = [
                    ucfirst($category),
                    $scoreData['score'] ?? 0,
                    $scoreData['max_score'] ?? 100,
                    $scoreData['weight'] ?? 0,
                    $this->getScoreGrade($scoreData['score'] ?? 0),
                    count($scoreData['issues'] ?? []),
                    count($scoreData['recommendations'] ?? [])
                ];
            }
        }

        // Add overall score row
        $data[] = [
            'OVERALL',
            $scores['overall'] ?? 0,
            100,
            100,
            $this->getScoreGrade($scores['overall'] ?? 0),
            $this->reportData['summary']['total_issues'],
            $this->reportData['summary']['total_warnings']
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Category',
            'Score',
            'Max Score',
            'Weight (%)',
            'Grade',
            'Issues',
            'Recommendations'
        ];
    }

    public function title(): string
    {
        return 'SEO Scores';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4CAF50']]],
            "A{$lastRow}:G{$lastRow}" => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']]]
        ];
    }

    private function getScoreGrade(int $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
}

/**
 * Issues Sheet - All issues and recommendations
 */
class IssuesSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $data = [];
        $summary = $this->reportData['summary'];
        $recommendations = $this->reportData['recommendations'] ?? [];

        // Add issues
        foreach ($summary['issues'] ?? [] as $issue) {
            $data[] = ['Critical Issue', $issue, 'High', 'Fix immediately'];
        }

        // Add warnings
        foreach ($summary['warnings'] ?? [] as $warning) {
            $data[] = ['Warning', $warning, 'Medium', 'Improve when possible'];
        }

        // Add detailed recommendations
        foreach ($recommendations as $rec) {
            $priority = ucfirst($rec['type'] ?? 'improvement');
            $category = ucfirst($rec['category'] ?? 'general');
            $data[] = [
                $priority,
                $rec['issue'] ?? '',
                $this->getPriorityLevel($rec['type'] ?? ''),
                $rec['recommendation'] ?? ''
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Type', 'Issue', 'Priority', 'Recommendation'];
    }

    public function title(): string
    {
        return 'Issues & Recommendations';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF9800']]]
        ];
    }

    private function getPriorityLevel(string $type): string
    {
        switch ($type) {
            case 'critical': return 'High';
            case 'important': return 'Medium';
            case 'improvement': return 'Low';
            default: return 'Medium';
        }
    }
}

/**
 * Technical Sheet - Technical SEO analysis
 */
class TechnicalSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $technical = $this->reportData['analysis_data']['technical'] ?? [];
        $performance = $this->reportData['analysis_data']['performance'] ?? [];

        $data = [
            ['Technical SEO Factors', ''],
            ['Status Code', $technical['status_code'] ?? 'N/A'],
            ['SSL/HTTPS', $technical['ssl_required'] ?? false ? 'Yes' : 'No'],
            ['Mobile Friendly', $technical['mobile_friendly'] ?? false ? 'Yes' : 'No'],
            ['Schema Markup', $technical['schema_markup_present'] ?? false ? 'Present' : 'Missing'],
            [''],
            ['Performance Metrics', ''],
            ['Load Time (seconds)', $performance['load_time'] ?? 'N/A'],
            ['HTML Size (KB)', $performance['html_size_kb'] ?? 'N/A'],
            [''],
            ['Core Web Vitals', ''],
            ['LCP (seconds)', $performance['core_web_vitals']['lcp'] ?? 'N/A'],
            ['FID (milliseconds)', $performance['core_web_vitals']['fid'] ?? 'N/A'],
            ['CLS', $performance['core_web_vitals']['cls'] ?? 'N/A'],
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Technical Analysis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:A' => ['font' => ['bold' => true]],
            'A1:B1' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2196F3']]],
            'A7:B7' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4CAF50']]],
            'A11:B11' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF9800']]]
        ];
    }
}

/**
 * Content Sheet - Content analysis details
 */
class ContentSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $content = $this->reportData['analysis_data']['content'] ?? [];
        $headings = $this->reportData['analysis_data']['headings'] ?? [];
        $links = $this->reportData['analysis_data']['links'] ?? [];
        $images = $this->reportData['analysis_data']['images'] ?? [];

        $data = [
            ['Content Metrics', ''],
            ['Word Count', $content['word_count'] ?? 'N/A'],
            ['Reading Time (minutes)', $content['reading_time_minutes'] ?? 'N/A'],
            ['Text to HTML Ratio (%)', $content['text_to_html_ratio'] ?? 'N/A'],
            ['Paragraphs', $content['paragraph_count'] ?? 'N/A'],
            [''],
            ['Heading Structure', ''],
            ['H1 Tags', count($headings['h1'] ?? [])],
            ['H2 Tags', count($headings['h2'] ?? [])],
            ['H3 Tags', count($headings['h3'] ?? [])],
            ['H4 Tags', count($headings['h4'] ?? [])],
            ['H5 Tags', count($headings['h5'] ?? [])],
            ['H6 Tags', count($headings['h6'] ?? [])],
            [''],
            ['Links Analysis', ''],
            ['Total Links', $links['total_count'] ?? 'N/A'],
            ['Internal Links', $links['internal_count'] ?? 'N/A'],
            ['External Links', $links['external_count'] ?? 'N/A'],
            ['Nofollow Links', $links['nofollow_count'] ?? 'N/A'],
            [''],
            ['Images Analysis', ''],
            ['Total Images', $images['total_count'] ?? 'N/A'],
            ['Images with Alt Text', ($images['total_count'] ?? 0) - ($images['without_alt_count'] ?? 0)],
            ['Images without Alt Text', $images['without_alt_count'] ?? 'N/A'],
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Content Analysis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:A' => ['font' => ['bold' => true]],
            'A1:B1' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '9C27B0']]],
            'A7:B7' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '3F51B5']]],
            'A15:B15' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '009688']]],
            'A21:B21' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF5722']]]
        ];
    }
}

/**
 * Meta Tags Sheet - Meta tags analysis
 */
class MetaTagsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $meta = $this->reportData['analysis_data']['meta'] ?? [];

        $data = [
            ['Meta Tag', 'Content', 'Length', 'Status'],
            ['Title', $meta['title'] ?? '', $meta['title_length'] ?? 0, $this->getTitleStatus($meta)],
            ['Description', $meta['description'] ?? '', $meta['description_length'] ?? 0, $this->getDescriptionStatus($meta)],
            ['Keywords', $meta['keywords'] ?? 'Not set', strlen($meta['keywords'] ?? ''), $meta['keywords'] ? 'Present' : 'Missing'],
            ['Canonical URL', $meta['canonical'] ?? 'Not set', strlen($meta['canonical'] ?? ''), $meta['canonical'] ? 'Present' : 'Missing'],
            ['Robots', $meta['robots'] ?? 'Not set', strlen($meta['robots'] ?? ''), $meta['robots'] ? 'Present' : 'Default'],
            [''],
            ['Open Graph Tags', '', '', ''],
            ['OG Title', $meta['og_title'] ?? 'Not set', strlen($meta['og_title'] ?? ''), $meta['og_title'] ? 'Present' : 'Missing'],
            ['OG Description', $meta['og_description'] ?? 'Not set', strlen($meta['og_description'] ?? ''), $meta['og_description'] ? 'Present' : 'Missing'],
            ['OG Image', $meta['og_image'] ?? 'Not set', strlen($meta['og_image'] ?? ''), $meta['og_image'] ? 'Present' : 'Missing'],
            ['OG Type', $meta['og_type'] ?? 'Not set', strlen($meta['og_type'] ?? ''), $meta['og_type'] ? 'Present' : 'Missing'],
            [''],
            ['Twitter Cards', '', '', ''],
            ['Twitter Card', $meta['twitter_card'] ?? 'Not set', strlen($meta['twitter_card'] ?? ''), $meta['twitter_card'] ? 'Present' : 'Missing'],
            ['Twitter Title', $meta['twitter_title'] ?? 'Not set', strlen($meta['twitter_title'] ?? ''), $meta['twitter_title'] ? 'Present' : 'Missing'],
            ['Twitter Description', $meta['twitter_description'] ?? 'Not set', strlen($meta['twitter_description'] ?? ''), $meta['twitter_description'] ? 'Present' : 'Missing'],
            ['Twitter Image', $meta['twitter_image'] ?? 'Not set', strlen($meta['twitter_image'] ?? ''), $meta['twitter_image'] ? 'Present' : 'Missing'],
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return ['Meta Tag', 'Content', 'Length', 'Status'];
    }

    public function title(): string
    {
        return 'Meta Tags';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E91E63']]],
            'A8:D8' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']]],
            'A14:D14' => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E8']]]
        ];
    }

    private function getTitleStatus(array $meta): string
    {
        $length = $meta['title_length'] ?? 0;
        if ($length === 0) return 'Missing';
        if ($length < 30) return 'Too Short';
        if ($length > 60) return 'Too Long';
        return 'Optimal';
    }

    private function getDescriptionStatus(array $meta): string
    {
        $length = $meta['description_length'] ?? 0;
        if ($length === 0) return 'Missing';
        if ($length < 120) return 'Too Short';
        if ($length > 160) return 'Too Long';
        return 'Optimal';
    }
}

/**
 * Raw Data Sheet - Complete analysis data
 */
class RawDataSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $data = [
            ['Section', 'Key', 'Value'],
            ['Report', 'URL', $this->reportData['url']],
            ['Report', 'Generated At', $this->reportData['generated_at']->toISOString()],
            ['Report', 'Report ID', $this->reportData['report_id']],
        ];

        // Flatten analysis data
        $analysisData = $this->reportData['analysis_data'] ?? [];
        foreach ($analysisData as $section => $sectionData) {
            if (is_array($sectionData)) {
                foreach ($sectionData as $key => $value) {
                    $data[] = [
                        ucfirst($section),
                        $key,
                        is_array($value) ? json_encode($value) : (string)$value
                    ];
                }
            } else {
                $data[] = [ucfirst($section), '', (string)$sectionData];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Section', 'Key', 'Value'];
    }

    public function title(): string
    {
        return 'Raw Data';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '607D8B']]]
        ];
    }
}

/**
 * Batch Summary Sheet for combined reports
 */
class BatchSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $combinedData;

    public function __construct(array $combinedData)
    {
        $this->combinedData = $combinedData;
    }

    public function collection()
    {
        $summary = $this->combinedData['summary'];
        $reports = $this->combinedData['reports'];

        $successfulReports = array_filter($reports, fn($r) => $r['success'] ?? true);
        $failedReports = count($reports) - count($successfulReports);

        return collect([
            ['Batch Summary', ''],
            ['Batch ID', $this->combinedData['batch_id']],
            ['Generated At', $summary['generated_at']->format('Y-m-d H:i:s')],
            [''],
            ['Report Statistics', ''],
            ['Total URLs', $summary['total_urls']],
            ['Successful Reports', $summary['successful_reports']],
            ['Failed Reports', $failedReports],
            [''],
            ['SEO Performance', ''],
            ['Average SEO Score', $summary['average_seo_score']],
            ['Total Issues Found', $summary['total_issues_found']],
            [''],
            ['Score Distribution', ''],
            ['Excellent (90-100)', $this->countScoreRange($successfulReports, 90, 100)],
            ['Good (80-89)', $this->countScoreRange($successfulReports, 80, 89)],
            ['Fair (70-79)', $this->countScoreRange($successfulReports, 70, 79)],
            ['Poor (60-69)', $this->countScoreRange($successfulReports, 60, 69)],
            ['Critical (<60)', $this->countScoreRange($successfulReports, 0, 59)],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Batch Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '3F51B5']]],
            'A:A' => ['font' => ['bold' => true]]
        ];
    }

    private function countScoreRange(array $reports, int $min, int $max): int
    {
        return count(array_filter($reports, function($report) use ($min, $max) {
            $score = $report['seo_score']['overall'] ?? 0;
            return $score >= $min && $score <= $max;
        }));
    }
}

/**
 * All Scores Sheet for combined reports
 */
class AllScoresSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $combinedData;

    public function __construct(array $combinedData)
    {
        $this->combinedData = $combinedData;
    }

    public function collection()
    {
        $data = [];
        $reports = array_filter($this->combinedData['reports'], fn($r) => $r['success'] ?? true);

        foreach ($reports as $report) {
            $scores = $report['seo_score'] ?? [];
            $summary = $report['summary'] ?? [];

            $data[] = [
                $report['url'],
                $scores['overall'] ?? 0,
                $summary['grade'] ?? 'N/A',
                $scores['title']['score'] ?? 0,
                $scores['content']['score'] ?? 0,
                $scores['technical']['score'] ?? 0,
                $summary['total_issues'] ?? 0,
                $summary['total_warnings'] ?? 0,
                $summary['total_successes'] ?? 0
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'URL',
            'Overall Score',
            'Grade',
            'Title Score',
            'Content Score',
            'Technical Score',
            'Issues',
            'Warnings',
            'Successes'
        ];
    }

    public function title(): string
    {
        return 'All Scores';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4CAF50']]]
        ];
    }
}

/**
 * Issues Overview Sheet for combined reports
 */
class IssuesOverviewSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $combinedData;

    public function __construct(array $combinedData)
    {
        $this->combinedData = $combinedData;
    }

    public function collection()
    {
        $data = [];
        $reports = array_filter($this->combinedData['reports'], fn($r) => $r['success'] ?? true);

        foreach ($reports as $report) {
            $url = $report['url'];
            $summary = $report['summary'] ?? [];

            // Add issues for this URL
            foreach ($summary['issues'] ?? [] as $issue) {
                $data[] = [$url, 'Critical Issue', $issue];
            }

            // Add warnings for this URL
            foreach ($summary['warnings'] ?? [] as $warning) {
                $data[] = [$url, 'Warning', $warning];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['URL', 'Type', 'Issue/Warning'];
    }

    public function title(): string
    {
        return 'Issues Overview';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF9800']]]
        ];
    }
}

/**
 * Detailed Reports Sheet for combined reports
 */
class DetailedReportsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private array $combinedData;

    public function __construct(array $combinedData)
    {
        $this->combinedData = $combinedData;
    }

    public function collection()
    {
        $data = [];
        $reports = array_filter($this->combinedData['reports'], fn($r) => $r['success'] ?? true);

        foreach ($reports as $report) {
            $meta = $report['analysis_data']['meta'] ?? [];
            $content = $report['analysis_data']['content'] ?? [];
            $technical = $report['analysis_data']['technical'] ?? [];

            $data[] = [
                $report['url'],
                $report['seo_score']['overall'] ?? 0,
                $meta['title'] ?? '',
                $meta['title_length'] ?? 0,
                $meta['description'] ?? '',
                $meta['description_length'] ?? 0,
                $content['word_count'] ?? 0,
                $technical['status_code'] ?? '',
                $technical['mobile_friendly'] ?? false ? 'Yes' : 'No',
                $report['generated_at']->format('Y-m-d H:i:s')
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'URL',
            'SEO Score',
            'Title',
            'Title Length',
            'Description',
            'Description Length',
            'Word Count',
            'Status Code',
            'Mobile Friendly',
            'Generated At'
        ];
    }

    public function title(): string
    {
        return 'Detailed Reports';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '9C27B0']]]
        ];
    }
}