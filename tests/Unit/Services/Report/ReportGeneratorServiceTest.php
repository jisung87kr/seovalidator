<?php

namespace Tests\Unit\Services\Report;

use App\Services\Report\ReportGeneratorService;
use App\Services\Report\PdfReportService;
use App\Services\Report\ExcelReportService;
use App\Services\Report\EmailReportService;
use App\Services\Analysis\SeoMetrics;
use App\Services\Analysis\PerformanceAnalyzer;
use App\Services\Analysis\RecommendationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class ReportGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportGeneratorService $service;
    private $mockSeoMetrics;
    private $mockPerformanceAnalyzer;
    private $mockRecommendationEngine;
    private $mockPdfService;
    private $mockExcelService;
    private $mockEmailService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockSeoMetrics = Mockery::mock(SeoMetrics::class);
        $this->mockPerformanceAnalyzer = Mockery::mock(PerformanceAnalyzer::class);
        $this->mockRecommendationEngine = Mockery::mock(RecommendationEngine::class);
        $this->mockPdfService = Mockery::mock(PdfReportService::class);
        $this->mockExcelService = Mockery::mock(ExcelReportService::class);
        $this->mockEmailService = Mockery::mock(EmailReportService::class);

        // Create service with mocks
        $this->service = new ReportGeneratorService(
            $this->mockSeoMetrics,
            $this->mockPerformanceAnalyzer,
            $this->mockRecommendationEngine,
            $this->mockPdfService,
            $this->mockExcelService,
            $this->mockEmailService
        );

        // Setup storage fake
        Storage::fake('local');
    }

    public function test_can_generate_single_report_with_default_options()
    {
        // Arrange
        $analysisData = $this->createSampleAnalysisData();
        $options = ['user_id' => 1];

        $this->setupMockExpectations($analysisData);

        // Act
        $result = $this->service->generateReport($analysisData, $options);

        // Assert
        $this->assertArrayHasKey('report_id', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('seo_score', $result);
        $this->assertArrayHasKey('files', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertEquals('https://example.com', $result['url']);
        $this->assertEquals(['pdf', 'excel', 'json'], array_keys($result['files']));

        // Verify metadata was stored
        $this->assertTrue(Storage::exists("reports/metadata/{$result['report_id']}.json"));
    }

    public function test_can_generate_report_with_custom_formats()
    {
        // Arrange
        $analysisData = $this->createSampleAnalysisData();
        $options = [
            'user_id' => 1,
            'formats' => ['pdf']
        ];

        $this->setupMockExpectations($analysisData, ['pdf']);

        // Act
        $result = $this->service->generateReport($analysisData, $options);

        // Assert
        $this->assertEquals(['pdf'], array_keys($result['files']));
    }

    public function test_can_generate_report_with_email_delivery()
    {
        // Arrange
        $analysisData = $this->createSampleAnalysisData();
        $options = [
            'user_id' => 1,
            'email_to' => 'test@example.com'
        ];

        $this->setupMockExpectations($analysisData);

        $this->mockEmailService
            ->shouldReceive('sendReport')
            ->once()
            ->with('test@example.com', Mockery::type('array'), Mockery::type('array'));

        // Act
        $result = $this->service->generateReport($analysisData, $options);

        // Assert
        $this->assertNotEmpty($result['files']);
    }

    public function test_can_generate_bulk_reports()
    {
        // Arrange
        $urlsData = [
            $this->createSampleAnalysisData('https://example1.com'),
            $this->createSampleAnalysisData('https://example2.com'),
        ];
        $options = ['user_id' => 1];

        // Setup expectations for each URL
        foreach ($urlsData as $data) {
            $this->setupMockExpectations($data);
        }

        // Act
        $result = $this->service->generateBulkReports($urlsData, $options);

        // Assert
        $this->assertArrayHasKey('batch_id', $result);
        $this->assertArrayHasKey('total_urls', $result);
        $this->assertArrayHasKey('reports', $result);
        $this->assertArrayHasKey('generated_at', $result);

        $this->assertEquals(2, $result['total_urls']);
        $this->assertCount(2, $result['reports']);

        foreach ($result['reports'] as $report) {
            $this->assertArrayHasKey('report_id', $report);
            $this->assertArrayHasKey('url', $report);
            $this->assertArrayHasKey('seo_score', $report);
        }
    }

    public function test_can_generate_combined_bulk_reports()
    {
        // Arrange
        $urlsData = [
            $this->createSampleAnalysisData('https://example1.com'),
            $this->createSampleAnalysisData('https://example2.com'),
        ];
        $options = [
            'user_id' => 1,
            'combine_reports' => true,
            'formats' => ['pdf', 'excel']
        ];

        // Setup expectations
        foreach ($urlsData as $data) {
            $this->setupMockExpectations($data);
        }

        $this->mockPdfService
            ->shouldReceive('generateCombinedReport')
            ->once()
            ->andReturn('reports/pdf/combined_batch_123.pdf');

        $this->mockExcelService
            ->shouldReceive('generateCombinedReport')
            ->once()
            ->andReturn('reports/excel/combined_batch_123.xlsx');

        // Act
        $result = $this->service->generateBulkReports($urlsData, $options);

        // Assert
        $this->assertArrayHasKey('combined_report', $result);
        $this->assertArrayHasKey('files', $result['combined_report']);
        $this->assertEquals(['pdf', 'excel'], array_keys($result['combined_report']['files']));
    }

    public function test_can_generate_scheduled_report()
    {
        // Arrange
        $scheduleConfig = [
            'schedule_id' => 'schedule_123',
            'urls' => [
                $this->createSampleAnalysisData('https://example1.com'),
                $this->createSampleAnalysisData('https://example2.com'),
            ],
            'formats' => ['pdf'],
            'email_recipients' => ['test@example.com'],
            'template' => 'standard',
            'combine_reports' => true
        ];

        // Setup expectations
        foreach ($scheduleConfig['urls'] as $data) {
            $this->setupMockExpectations($data);
        }

        $this->mockPdfService
            ->shouldReceive('generateCombinedReport')
            ->once()
            ->andReturn('reports/pdf/combined_schedule_123.pdf');

        $this->mockEmailService
            ->shouldReceive('sendScheduledReport')
            ->once()
            ->with('test@example.com', Mockery::type('array'), $scheduleConfig);

        // Act
        $result = $this->service->generateScheduledReport($scheduleConfig);

        // Assert
        $this->assertArrayHasKey('batch_id', $result);
        $this->assertArrayHasKey('reports', $result);
        $this->assertArrayHasKey('combined_report', $result);
        $this->assertEquals(2, $result['total_urls']);
    }

    public function test_can_get_report_history()
    {
        // Arrange
        $userId = 1;
        $reportId1 = 'report_1';
        $reportId2 = 'report_2';

        // Create sample metadata files
        $metadata1 = [
            'report_id' => $reportId1,
            'url' => 'https://example1.com',
            'user_id' => $userId,
            'generated_at' => now()->subDay()->toISOString(),
            'seo_score' => 85
        ];

        $metadata2 = [
            'report_id' => $reportId2,
            'url' => 'https://example2.com',
            'user_id' => $userId,
            'generated_at' => now()->toISOString(),
            'seo_score' => 92
        ];

        Storage::put("reports/metadata/{$reportId1}.json", json_encode($metadata1));
        Storage::put("reports/metadata/{$reportId2}.json", json_encode($metadata2));

        // Act
        $history = $this->service->getReportHistory($userId);

        // Assert
        $this->assertCount(2, $history);
        $this->assertEquals($reportId2, $history[0]['report_id']); // Newest first
        $this->assertEquals($reportId1, $history[1]['report_id']);
    }

    public function test_can_cleanup_old_reports()
    {
        // Arrange
        $oldReportId = 'old_report';
        $newReportId = 'new_report';

        $oldMetadata = [
            'report_id' => $oldReportId,
            'generated_at' => now()->subDays(35)->toISOString()
        ];

        $newMetadata = [
            'report_id' => $newReportId,
            'generated_at' => now()->subDays(5)->toISOString()
        ];

        // Create files
        Storage::put("reports/metadata/{$oldReportId}.json", json_encode($oldMetadata));
        Storage::put("reports/metadata/{$newReportId}.json", json_encode($newMetadata));
        Storage::put("reports/pdf/{$oldReportId}.pdf", 'old pdf content');
        Storage::put("reports/pdf/{$newReportId}.pdf", 'new pdf content');

        // Act
        $deletedCount = $this->service->cleanupOldReports(30);

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertFalse(Storage::exists("reports/metadata/{$oldReportId}.json"));
        $this->assertFalse(Storage::exists("reports/pdf/{$oldReportId}.pdf"));
        $this->assertTrue(Storage::exists("reports/metadata/{$newReportId}.json"));
        $this->assertTrue(Storage::exists("reports/pdf/{$newReportId}.pdf"));
    }

    public function test_handles_partial_failure_in_bulk_reports()
    {
        // Arrange
        $urlsData = [
            $this->createSampleAnalysisData('https://valid.com'),
            $this->createSampleAnalysisData('https://invalid.com'),
        ];

        // Setup first URL to succeed
        $this->setupMockExpectations($urlsData[0]);

        // Setup second URL to fail
        $this->mockSeoMetrics
            ->shouldReceive('calculateAdvancedTitleScore')
            ->andThrow(new \Exception('Analysis failed'));

        // Act
        $result = $this->service->generateBulkReports($urlsData);

        // Assert
        $this->assertEquals(2, $result['total_urls']);
        $this->assertCount(2, $result['reports']);
        $this->assertTrue($result['reports'][0]['success'] ?? true);
        $this->assertFalse($result['reports'][1]['success'] ?? true);
        $this->assertArrayHasKey('error', $result['reports'][1]);
    }

    /**
     * Private helper methods
     */

    private function createSampleAnalysisData(string $url = 'https://example.com'): array
    {
        return [
            'url' => $url,
            'meta' => [
                'title' => 'Sample Page Title',
                'title_length' => 18,
                'description' => 'This is a sample meta description for testing purposes that is long enough.',
                'description_length' => 76,
                'canonical' => $url
            ],
            'content' => [
                'word_count' => 500,
                'text_to_html_ratio' => 25,
                'reading_time_minutes' => 2.5
            ],
            'technical' => [
                'status_code' => 200,
                'ssl_required' => true,
                'mobile_friendly' => true,
                'schema_markup_present' => false
            ],
            'performance' => [
                'load_time' => 2.1,
                'html_size_kb' => 45,
                'core_web_vitals' => [
                    'lcp' => 2.3,
                    'fid' => 85,
                    'cls' => 0.08
                ]
            ],
            'headings' => [
                'h1' => ['Main Heading'],
                'h2' => ['Subheading 1', 'Subheading 2'],
                'h3' => ['Sub-subheading']
            ],
            'images' => [
                'total_count' => 5,
                'without_alt_count' => 1
            ],
            'links' => [
                'total_count' => 10,
                'internal_count' => 7,
                'external_count' => 3,
                'nofollow_count' => 1
            ]
        ];
    }

    private function setupMockExpectations(array $analysisData, array $formats = ['pdf', 'excel', 'json']): void
    {
        // Mock SEO metrics calculations
        $this->mockSeoMetrics
            ->shouldReceive('calculateAdvancedTitleScore')
            ->andReturn([
                'score' => 85,
                'max_score' => 100,
                'weight' => 20,
                'issues' => [],
                'recommendations' => ['Optimize title length'],
                'metrics' => ['length' => 18]
            ]);

        $this->mockSeoMetrics
            ->shouldReceive('calculateAdvancedContentScore')
            ->andReturn([
                'score' => 78,
                'max_score' => 100,
                'weight' => 25,
                'issues' => ['Content too short'],
                'recommendations' => ['Add more content'],
                'metrics' => ['word_count' => 500]
            ]);

        $this->mockSeoMetrics
            ->shouldReceive('calculateTechnicalPerformanceScore')
            ->andReturn([
                'score' => 92,
                'max_score' => 100,
                'weight' => 20,
                'issues' => [],
                'recommendations' => [],
                'metrics' => ['load_time' => 2.1]
            ]);

        $this->mockSeoMetrics
            ->shouldReceive('getPrimaryWeights')
            ->andReturn([
                'title' => 20,
                'content' => 25,
                'technical' => 20,
                'images' => 15,
                'links' => 10,
                'meta_description' => 10
            ]);

        // Mock recommendation engine
        $this->mockRecommendationEngine
            ->shouldReceive('generateRecommendations')
            ->andReturn([
                [
                    'type' => 'improvement',
                    'category' => 'content',
                    'issue' => 'Content could be more comprehensive',
                    'recommendation' => 'Add more detailed information'
                ]
            ]);

        // Mock report services
        if (in_array('pdf', $formats)) {
            $this->mockPdfService
                ->shouldReceive('generateReport')
                ->andReturn('reports/pdf/test_report.pdf');
        }

        if (in_array('excel', $formats)) {
            $this->mockExcelService
                ->shouldReceive('generateReport')
                ->andReturn('reports/excel/test_report.xlsx');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}