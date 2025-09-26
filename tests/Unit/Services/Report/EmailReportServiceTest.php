<?php

namespace Tests\Unit\Services\Report;

use App\Services\Report\EmailReportService;
use App\Services\Report\SeoReportMail;
use App\Services\Report\ScheduledReportMail;
use App\Services\Report\CombinedReportMail;
use App\Services\Report\ReportSummaryNotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmailReportService();

        // Setup storage and mail fakes
        Storage::fake('local');
        Mail::fake();
    }

    public function test_can_send_individual_report()
    {
        // Arrange
        $email = 'test@example.com';
        $reportData = $this->createSampleReportData();
        $attachmentPaths = [
            'pdf' => 'reports/pdf/test_report.pdf',
            'excel' => 'reports/excel/test_report.xlsx'
        ];

        // Create actual attachment files
        Storage::put($attachmentPaths['pdf'], 'fake pdf content');
        Storage::put($attachmentPaths['excel'], 'fake excel content');

        // Act
        $this->service->sendReport($email, $reportData, $attachmentPaths);

        // Assert
        Mail::assertSent(SeoReportMail::class, function ($mail) use ($email, $reportData) {
            return $mail->hasTo($email) &&
                   $mail->reportData['url'] === $reportData['url'] &&
                   $mail->reportData['report_id'] === $reportData['report_id'];
        });
    }

    public function test_can_send_scheduled_report()
    {
        // Arrange
        $email = 'admin@example.com';
        $bulkResult = $this->createSampleBulkResult();
        $scheduleConfig = $this->createSampleScheduleConfig();

        // Act
        $this->service->sendScheduledReport($email, $bulkResult, $scheduleConfig);

        // Assert
        Mail::assertSent(ScheduledReportMail::class, function ($mail) use ($email, $bulkResult, $scheduleConfig) {
            return $mail->hasTo($email) &&
                   $mail->bulkResult['batch_id'] === $bulkResult['batch_id'] &&
                   $mail->scheduleConfig['schedule_id'] === $scheduleConfig['schedule_id'];
        });
    }

    public function test_can_send_combined_report()
    {
        // Arrange
        $email = 'manager@example.com';
        $combinedData = $this->createSampleCombinedData();
        $attachmentPaths = [
            'pdf' => 'reports/pdf/combined_batch_123.pdf',
            'excel' => 'reports/excel/combined_batch_123.xlsx'
        ];

        // Create attachment files
        Storage::put($attachmentPaths['pdf'], 'fake combined pdf content');
        Storage::put($attachmentPaths['excel'], 'fake combined excel content');

        // Act
        $this->service->sendCombinedReport($email, $combinedData, $attachmentPaths);

        // Assert
        Mail::assertSent(CombinedReportMail::class, function ($mail) use ($email, $combinedData) {
            return $mail->hasTo($email) &&
                   $mail->combinedData['batch_id'] === $combinedData['batch_id'];
        });
    }

    public function test_can_send_summary_notification()
    {
        // Arrange
        $email = 'user@example.com';
        $reportData = $this->createSampleReportData();

        // Act
        $this->service->sendReportSummaryNotification($email, $reportData);

        // Assert
        Mail::assertSent(ReportSummaryNotificationMail::class, function ($mail) use ($email, $reportData) {
            return $mail->hasTo($email) &&
                   $mail->reportData['report_id'] === $reportData['report_id'];
        });
    }

    public function test_can_send_bulk_reports_to_multiple_recipients()
    {
        // Arrange
        $recipients = ['user1@example.com', 'user2@example.com', 'user3@example.com'];
        $reportData = $this->createSampleReportData();
        $attachmentPaths = [
            'pdf' => 'reports/pdf/bulk_test.pdf'
        ];

        Storage::put($attachmentPaths['pdf'], 'fake pdf content');

        // Act
        $results = $this->service->sendBulkReports($recipients, $reportData, $attachmentPaths);

        // Assert
        $this->assertCount(3, $results);
        foreach ($recipients as $recipient) {
            $this->assertTrue($results[$recipient]['success']);
        }

        Mail::assertSent(SeoReportMail::class, 3);
    }

    public function test_bulk_reports_handles_individual_failures()
    {
        // Arrange
        $recipients = ['valid@example.com', 'invalid@invalid-domain'];
        $reportData = $this->createSampleReportData();
        $attachmentPaths = ['pdf' => 'reports/pdf/test.pdf'];

        Storage::put($attachmentPaths['pdf'], 'fake content');

        // Simulate mail failure for invalid email
        Mail::shouldReceive('to')
            ->with('invalid@invalid-domain')
            ->andThrow(new \Exception('Invalid email address'));

        Mail::shouldReceive('to')
            ->with('valid@example.com')
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->with(\Mockery::type(SeoReportMail::class))
            ->andReturn();

        // Act
        $results = $this->service->sendBulkReports($recipients, $reportData, $attachmentPaths);

        // Assert
        $this->assertTrue($results['valid@example.com']['success']);
        $this->assertFalse($results['invalid@invalid-domain']['success']);
        $this->assertArrayHasKey('error', $results['invalid@invalid-domain']);
    }

    public function test_seo_report_mail_has_correct_subject()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'url' => 'https://test-site.com/page',
            'seo_score' => ['overall' => 87]
        ]);
        $attachmentPaths = [];

        // Act
        $mail = new SeoReportMail($reportData, $attachmentPaths);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContains('test-site.com', $envelope->subject);
        $this->assertStringContains('87/100', $envelope->subject);
    }

    public function test_scheduled_report_mail_has_correct_subject()
    {
        // Arrange
        $bulkResult = $this->createSampleBulkResult();
        $scheduleConfig = $this->createSampleScheduleConfig([
            'schedule_id' => 'daily_monitoring',
            'urls' => ['url1', 'url2', 'url3']
        ]);

        // Act
        $mail = new ScheduledReportMail($bulkResult, $scheduleConfig);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContains('daily_monitoring', $envelope->subject);
        $this->assertStringContains('3 URLs', $envelope->subject);
    }

    public function test_combined_report_mail_has_correct_subject()
    {
        // Arrange
        $combinedData = $this->createSampleCombinedData([
            'summary' => [
                'total_urls' => 5,
                'average_seo_score' => 78
            ]
        ]);
        $attachmentPaths = [];

        // Act
        $mail = new CombinedReportMail($combinedData, $attachmentPaths);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContains('5 URLs', $envelope->subject);
        $this->assertStringContains('78/100', $envelope->subject);
    }

    public function test_report_summary_notification_has_correct_subject()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'url' => 'https://my-website.com',
            'seo_score' => ['overall' => 92]
        ]);

        // Act
        $mail = new ReportSummaryNotificationMail($reportData);
        $envelope = $mail->envelope();

        // Assert
        $this->assertStringContains('my-website.com', $envelope->subject);
        $this->assertStringContains('92/100', $envelope->subject);
    }

    public function test_mail_attachments_are_included_when_files_exist()
    {
        // Arrange
        $reportData = $this->createSampleReportData();
        $attachmentPaths = [
            'pdf' => 'reports/pdf/test_with_attachments.pdf',
            'excel' => 'reports/excel/test_with_attachments.xlsx'
        ];

        // Create actual files
        Storage::put($attachmentPaths['pdf'], 'pdf content');
        Storage::put($attachmentPaths['excel'], 'excel content');

        // Act
        $mail = new SeoReportMail($reportData, $attachmentPaths);
        $attachments = $mail->attachments();

        // Assert
        $this->assertCount(2, $attachments);
    }

    public function test_mail_skips_non_existent_attachment_files()
    {
        // Arrange
        $reportData = $this->createSampleReportData();
        $attachmentPaths = [
            'pdf' => 'reports/pdf/non_existent.pdf',
            'excel' => 'reports/excel/also_non_existent.xlsx'
        ];

        // Don't create the files

        // Act
        $mail = new SeoReportMail($reportData, $attachmentPaths);
        $attachments = $mail->attachments();

        // Assert
        $this->assertCount(0, $attachments);
    }

    public function test_mail_generates_appropriate_attachment_filenames()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'url' => 'https://my-domain.com/page',
            'generated_at' => now()->setDate(2024, 3, 15)
        ]);
        $attachmentPaths = [
            'pdf' => 'reports/pdf/test.pdf',
            'excel' => 'reports/excel/test.xlsx'
        ];

        Storage::put($attachmentPaths['pdf'], 'content');
        Storage::put($attachmentPaths['excel'], 'content');

        // Act
        $mail = new SeoReportMail($reportData, $attachmentPaths);
        $attachments = $mail->attachments();

        // Assert
        $this->assertCount(2, $attachments);

        $pdfAttachment = $attachments[0];
        $excelAttachment = $attachments[1];

        $this->assertStringContains('my-domain.com', $pdfAttachment->as);
        $this->assertStringContains('2024-03-15', $pdfAttachment->as);
        $this->assertStringEndsWith('.pdf', $pdfAttachment->as);

        $this->assertStringContains('my-domain.com', $excelAttachment->as);
        $this->assertStringContains('2024-03-15', $excelAttachment->as);
        $this->assertStringEndsWith('.xlsx', $excelAttachment->as);
    }

    public function test_handles_mail_sending_exceptions()
    {
        // Arrange
        $email = 'test@example.com';
        $reportData = $this->createSampleReportData();
        $attachmentPaths = [];

        // Make Mail throw an exception
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP connection failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $this->service->sendReport($email, $reportData, $attachmentPaths);
    }

    /**
     * Private helper methods
     */

    private function createSampleReportData(array $overrides = []): array
    {
        $defaultData = [
            'report_id' => 'test_report_' . uniqid(),
            'url' => 'https://example.com',
            'generated_at' => now(),
            'seo_score' => ['overall' => 85],
            'summary' => [
                'grade' => 'A',
                'total_issues' => 3,
                'total_warnings' => 5,
                'total_successes' => 12,
                'issues' => ['Missing meta description', 'Image without alt text'],
                'warnings' => ['Title too long', 'Content could be expanded'],
                'successes' => ['Fast loading', 'Mobile responsive'],
                'key_insights' => ['Good overall performance', 'Minor optimizations needed']
            ]
        ];

        return array_merge_recursive($defaultData, $overrides);
    }

    private function createSampleBulkResult(array $overrides = []): array
    {
        $defaultData = [
            'batch_id' => 'batch_' . uniqid(),
            'total_urls' => 2,
            'reports' => [
                ['url' => 'https://example1.com', 'success' => true],
                ['url' => 'https://example2.com', 'success' => true]
            ],
            'generated_at' => now(),
            'combined_report' => [
                'summary' => ['average_seo_score' => 82]
            ]
        ];

        return array_merge_recursive($defaultData, $overrides);
    }

    private function createSampleScheduleConfig(array $overrides = []): array
    {
        $defaultData = [
            'schedule_id' => 'schedule_' . uniqid(),
            'frequency' => 'daily',
            'urls' => ['https://example.com']
        ];

        return array_merge($defaultData, $overrides);
    }

    private function createSampleCombinedData(array $overrides = []): array
    {
        $defaultData = [
            'batch_id' => 'combined_' . uniqid(),
            'reports' => [
                ['url' => 'https://site1.com', 'success' => true],
                ['url' => 'https://site2.com', 'success' => true]
            ],
            'summary' => [
                'total_urls' => 2,
                'successful_reports' => 2,
                'average_seo_score' => 80,
                'generated_at' => now()
            ]
        ];

        return array_merge_recursive($defaultData, $overrides);
    }
}