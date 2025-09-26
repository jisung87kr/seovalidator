<?php

namespace App\Services\Report;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

/**
 * Email Report Delivery Service
 *
 * Handles email delivery of SEO reports with attachments,
 * customizable templates, and scheduled delivery options.
 */
class EmailReportService
{
    /**
     * Send individual report via email
     */
    public function sendReport(string $email, array $reportData, array $attachmentPaths): void
    {
        $reportId = $reportData['report_id'];
        $url = $reportData['url'];

        Log::info('Sending SEO report via email', [
            'report_id' => $reportId,
            'url' => $url,
            'recipient' => $email,
            'attachments' => array_keys($attachmentPaths)
        ]);

        try {
            $mailable = new SeoReportMail($reportData, $attachmentPaths);
            Mail::to($email)->send($mailable);

            Log::info('SEO report email sent successfully', [
                'report_id' => $reportId,
                'recipient' => $email
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send SEO report email', [
                'report_id' => $reportId,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send scheduled report via email
     */
    public function sendScheduledReport(string $email, array $bulkResult, array $scheduleConfig): void
    {
        $scheduleId = $scheduleConfig['schedule_id'];
        $batchId = $bulkResult['batch_id'];

        Log::info('Sending scheduled report via email', [
            'schedule_id' => $scheduleId,
            'batch_id' => $batchId,
            'recipient' => $email
        ]);

        try {
            $mailable = new ScheduledReportMail($bulkResult, $scheduleConfig);
            Mail::to($email)->send($mailable);

            Log::info('Scheduled report email sent successfully', [
                'schedule_id' => $scheduleId,
                'recipient' => $email
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send scheduled report email', [
                'schedule_id' => $scheduleId,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send combined report via email
     */
    public function sendCombinedReport(string $email, array $combinedData, array $attachmentPaths): void
    {
        $batchId = $combinedData['batch_id'];

        Log::info('Sending combined report via email', [
            'batch_id' => $batchId,
            'recipient' => $email,
            'attachments' => array_keys($attachmentPaths)
        ]);

        try {
            $mailable = new CombinedReportMail($combinedData, $attachmentPaths);
            Mail::to($email)->send($mailable);

            Log::info('Combined report email sent successfully', [
                'batch_id' => $batchId,
                'recipient' => $email
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send combined report email', [
                'batch_id' => $batchId,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send report summary notification
     */
    public function sendReportSummaryNotification(string $email, array $reportData): void
    {
        $reportId = $reportData['report_id'];
        $url = $reportData['url'];

        Log::info('Sending report summary notification', [
            'report_id' => $reportId,
            'url' => $url,
            'recipient' => $email
        ]);

        try {
            $mailable = new ReportSummaryNotificationMail($reportData);
            Mail::to($email)->send($mailable);

            Log::info('Report summary notification sent successfully', [
                'report_id' => $reportId,
                'recipient' => $email
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send report summary notification', [
                'report_id' => $reportId,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send bulk email to multiple recipients
     */
    public function sendBulkReports(array $recipients, array $reportData, array $attachmentPaths): array
    {
        $results = [];
        $reportId = $reportData['report_id'];

        Log::info('Sending bulk report emails', [
            'report_id' => $reportId,
            'recipient_count' => count($recipients)
        ]);

        foreach ($recipients as $recipient) {
            try {
                $this->sendReport($recipient, $reportData, $attachmentPaths);
                $results[$recipient] = ['success' => true];

            } catch (\Exception $e) {
                $results[$recipient] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));

        Log::info('Bulk report emails completed', [
            'report_id' => $reportId,
            'total_recipients' => count($recipients),
            'successful_sends' => $successCount,
            'failed_sends' => count($recipients) - $successCount
        ]);

        return $results;
    }
}

/**
 * Individual SEO Report Email
 */
class SeoReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;
    public array $attachmentPaths;

    public function __construct(array $reportData, array $attachmentPaths)
    {
        $this->reportData = $reportData;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function envelope(): Envelope
    {
        $url = parse_url($this->reportData['url'], PHP_URL_HOST) ?? $this->reportData['url'];
        $score = $this->reportData['seo_score']['overall'] ?? 0;

        return new Envelope(
            subject: "SEO Analysis Report for {$url} (Score: {$score}/100)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seo-report',
            with: [
                'reportData' => $this->reportData,
                'url' => $this->reportData['url'],
                'score' => $this->reportData['seo_score']['overall'] ?? 0,
                'grade' => $this->reportData['summary']['grade'] ?? 'N/A',
                'issues' => $this->reportData['summary']['issues'] ?? [],
                'warnings' => $this->reportData['summary']['warnings'] ?? [],
                'successes' => $this->reportData['summary']['successes'] ?? [],
                'insights' => $this->reportData['summary']['key_insights'] ?? [],
                'generatedAt' => $this->reportData['generated_at']->format('F j, Y \a\t g:i A'),
                'reportId' => $this->reportData['report_id']
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachmentPaths as $format => $path) {
            if (Storage::exists($path)) {
                $filename = $this->generateAttachmentFilename($format);
                $attachments[] = Attachment::fromStorage($path)->as($filename);
            }
        }

        return $attachments;
    }

    private function generateAttachmentFilename(string $format): string
    {
        $domain = parse_url($this->reportData['url'], PHP_URL_HOST) ?? 'report';
        $date = $this->reportData['generated_at']->format('Y-m-d');
        $extension = $format === 'excel' ? 'xlsx' : $format;

        return "seo-report-{$domain}-{$date}.{$extension}";
    }
}

/**
 * Scheduled Report Email
 */
class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $bulkResult;
    public array $scheduleConfig;

    public function __construct(array $bulkResult, array $scheduleConfig)
    {
        $this->bulkResult = $bulkResult;
        $this->scheduleConfig = $scheduleConfig;
    }

    public function envelope(): Envelope
    {
        $scheduleId = $this->scheduleConfig['schedule_id'];
        $urlCount = count($this->scheduleConfig['urls']);
        $avgScore = $this->bulkResult['combined_report']['summary']['average_seo_score'] ?? 0;

        return new Envelope(
            subject: "Scheduled SEO Report #{$scheduleId} - {$urlCount} URLs (Avg Score: {$avgScore}/100)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled-report',
            with: [
                'bulkResult' => $this->bulkResult,
                'scheduleConfig' => $this->scheduleConfig,
                'batchId' => $this->bulkResult['batch_id'],
                'totalUrls' => $this->bulkResult['total_urls'],
                'successfulReports' => count(array_filter($this->bulkResult['reports'], fn($r) => $r['success'] ?? true)),
                'averageScore' => $this->bulkResult['combined_report']['summary']['average_seo_score'] ?? 0,
                'generatedAt' => $this->bulkResult['generated_at']->format('F j, Y \a\t g:i A'),
                'scheduleFrequency' => $this->scheduleConfig['frequency'] ?? 'manual'
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if (isset($this->bulkResult['combined_report']['files'])) {
            foreach ($this->bulkResult['combined_report']['files'] as $format => $path) {
                if (Storage::exists($path)) {
                    $filename = $this->generateScheduledAttachmentFilename($format);
                    $attachments[] = Attachment::fromStorage($path)->as($filename);
                }
            }
        }

        return $attachments;
    }

    private function generateScheduledAttachmentFilename(string $format): string
    {
        $scheduleId = $this->scheduleConfig['schedule_id'];
        $date = $this->bulkResult['generated_at']->format('Y-m-d');
        $extension = $format === 'excel' ? 'xlsx' : $format;

        return "scheduled-seo-report-{$scheduleId}-{$date}.{$extension}";
    }
}

/**
 * Combined Report Email
 */
class CombinedReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $combinedData;
    public array $attachmentPaths;

    public function __construct(array $combinedData, array $attachmentPaths)
    {
        $this->combinedData = $combinedData;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function envelope(): Envelope
    {
        $urlCount = $this->combinedData['summary']['total_urls'];
        $avgScore = $this->combinedData['summary']['average_seo_score'];

        return new Envelope(
            subject: "Combined SEO Report - {$urlCount} URLs (Avg Score: {$avgScore}/100)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.combined-report',
            with: [
                'combinedData' => $this->combinedData,
                'batchId' => $this->combinedData['batch_id'],
                'summary' => $this->combinedData['summary'],
                'reports' => array_filter($this->combinedData['reports'], fn($r) => $r['success'] ?? true),
                'failedReports' => array_filter($this->combinedData['reports'], fn($r) => !($r['success'] ?? true)),
                'generatedAt' => $this->combinedData['summary']['generated_at']->format('F j, Y \a\t g:i A')
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachmentPaths as $format => $path) {
            if (Storage::exists($path)) {
                $filename = $this->generateCombinedAttachmentFilename($format);
                $attachments[] = Attachment::fromStorage($path)->as($filename);
            }
        }

        return $attachments;
    }

    private function generateCombinedAttachmentFilename(string $format): string
    {
        $batchId = $this->combinedData['batch_id'];
        $date = $this->combinedData['summary']['generated_at']->format('Y-m-d');
        $extension = $format === 'excel' ? 'xlsx' : $format;

        return "combined-seo-report-{$batchId}-{$date}.{$extension}";
    }
}

/**
 * Report Summary Notification Email
 */
class ReportSummaryNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function envelope(): Envelope
    {
        $url = parse_url($this->reportData['url'], PHP_URL_HOST) ?? $this->reportData['url'];
        $score = $this->reportData['seo_score']['overall'] ?? 0;

        return new Envelope(
            subject: "SEO Report Ready for {$url} - Score: {$score}/100",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-summary-notification',
            with: [
                'reportData' => $this->reportData,
                'url' => $this->reportData['url'],
                'score' => $this->reportData['seo_score']['overall'] ?? 0,
                'grade' => $this->reportData['summary']['grade'] ?? 'N/A',
                'totalIssues' => $this->reportData['summary']['total_issues'] ?? 0,
                'totalWarnings' => $this->reportData['summary']['total_warnings'] ?? 0,
                'topIssues' => array_slice($this->reportData['summary']['issues'] ?? [], 0, 3),
                'keyInsights' => array_slice($this->reportData['summary']['key_insights'] ?? [], 0, 2),
                'generatedAt' => $this->reportData['generated_at']->format('F j, Y \a\t g:i A'),
                'reportId' => $this->reportData['report_id'],
                'downloadUrl' => route('reports.download', $this->reportData['report_id'])
            ]
        );
    }
}