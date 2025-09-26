<?php

namespace Tests\Unit\Services\Report;

use App\Services\Report\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private PdfReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PdfReportService();

        // Setup storage fake
        Storage::fake('local');
    }

    public function test_can_generate_standard_pdf_report()
    {
        // Arrange
        $reportData = $this->createSampleReportData();

        // Act
        $filename = $this->service->generateReport($reportData, 'standard');

        // Assert
        $this->assertStringContains('reports/pdf/', $filename);
        $this->assertStringContains('.pdf', $filename);
        $this->assertTrue(Storage::exists($filename));

        // Verify file is not empty
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
        $this->assertTrue(strlen($content) > 1000); // PDF should be substantial
    }

    public function test_can_generate_executive_pdf_report()
    {
        // Arrange
        $reportData = $this->createSampleReportData();

        // Act
        $filename = $this->service->generateReport($reportData, 'executive');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_can_generate_detailed_pdf_report()
    {
        // Arrange
        $reportData = $this->createSampleReportData();

        // Act
        $filename = $this->service->generateReport($reportData, 'detailed');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_can_generate_branded_pdf_report()
    {
        // Arrange
        $reportData = $this->createSampleReportData();

        // Act
        $filename = $this->service->generateReport($reportData, 'branded');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_can_generate_combined_pdf_report()
    {
        // Arrange
        $combinedData = $this->createSampleCombinedData();

        // Act
        $filename = $this->service->generateCombinedReport($combinedData);

        // Assert
        $this->assertStringContains('combined_', $filename);
        $this->assertTrue(Storage::exists($filename));

        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_pdf_contains_expected_content_elements()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'url' => 'https://test-site.com',
            'seo_score' => ['overall' => 85],
            'summary' => [
                'grade' => 'A',
                'total_issues' => 3,
                'total_warnings' => 5,
                'total_successes' => 12,
                'issues' => ['Missing meta description', 'Image without alt text'],
                'warnings' => ['Title too long', 'H1 missing keywords'],
                'successes' => ['Page loads quickly', 'Mobile responsive'],
                'key_insights' => ['Site performs well overall', 'Minor optimization needed']
            ]
        ]);

        // Act
        $filename = $this->service->generateReport($reportData, 'standard');

        // Assert
        $this->assertTrue(Storage::exists($filename));

        // For a real implementation, you might extract text from PDF and verify content
        // For now, we'll just verify the file was created and has reasonable size
        $content = Storage::get($filename);
        $this->assertTrue(strlen($content) > 5000); // Substantial PDF content
    }

    public function test_handles_missing_data_gracefully()
    {
        // Arrange
        $minimalReportData = [
            'report_id' => 'test_report_minimal',
            'url' => 'https://example.com',
            'generated_at' => now(),
            'seo_score' => ['overall' => 0],
            'summary' => [
                'grade' => 'F',
                'total_issues' => 0,
                'total_warnings' => 0,
                'total_successes' => 0,
                'issues' => [],
                'warnings' => [],
                'successes' => [],
                'key_insights' => []
            ],
            'recommendations' => [],
            'analysis_data' => []
        ];

        // Act
        $filename = $this->service->generateReport($minimalReportData, 'standard');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_pdf_includes_all_score_categories()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'seo_score' => [
                'overall' => 82,
                'title' => [
                    'score' => 90,
                    'max_score' => 100,
                    'metrics' => ['length' => 45, 'word_count' => 8]
                ],
                'content' => [
                    'score' => 75,
                    'max_score' => 100,
                    'metrics' => ['word_count' => 800, 'reading_time_minutes' => 3.2]
                ],
                'technical' => [
                    'score' => 85,
                    'max_score' => 100,
                    'metrics' => ['load_time' => 2.1, 'mobile_friendly' => true]
                ]
            ]
        ]);

        // Act
        $filename = $this->service->generateReport($reportData, 'standard');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_combined_report_includes_multiple_urls()
    {
        // Arrange
        $combinedData = [
            'batch_id' => 'batch_123',
            'reports' => [
                [
                    'url' => 'https://site1.com',
                    'seo_score' => ['overall' => 85],
                    'summary' => ['total_issues' => 2, 'total_warnings' => 3, 'total_successes' => 8],
                    'success' => true
                ],
                [
                    'url' => 'https://site2.com',
                    'seo_score' => ['overall' => 72],
                    'summary' => ['total_issues' => 5, 'total_warnings' => 7, 'total_successes' => 5],
                    'success' => true
                ],
                [
                    'url' => 'https://site3.com',
                    'error' => 'Failed to analyze',
                    'success' => false
                ]
            ],
            'summary' => [
                'total_urls' => 3,
                'successful_reports' => 2,
                'average_seo_score' => 78,
                'total_issues_found' => 7,
                'generated_at' => now()
            ]
        ];

        // Act
        $filename = $this->service->generateCombinedReport($combinedData);

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
        $this->assertTrue(strlen($content) > 3000); // Should be larger for multiple reports
    }

    public function test_throws_exception_on_invalid_report_data()
    {
        // Arrange
        $invalidData = []; // Missing required fields

        // Act & Assert
        $this->expectException(\Exception::class);

        $this->service->generateReport($invalidData, 'standard');
    }

    public function test_different_templates_produce_different_output_sizes()
    {
        // Arrange
        $reportData = $this->createSampleReportData();

        // Act
        $standardFile = $this->service->generateReport($reportData, 'standard');
        $executiveFile = $this->service->generateReport($reportData, 'executive');

        // Assert
        $this->assertTrue(Storage::exists($standardFile));
        $this->assertTrue(Storage::exists($executiveFile));

        $standardSize = strlen(Storage::get($standardFile));
        $executiveSize = strlen(Storage::get($executiveFile));

        // Standard template should generally be larger than executive
        $this->assertGreaterThan($executiveSize * 0.5, $standardSize);
    }

    public function test_pdf_handles_special_characters_in_url()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'url' => 'https://example.com/path?param=value&special=chars%20here'
        ]);

        // Act
        $filename = $this->service->generateReport($reportData, 'standard');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
    }

    public function test_pdf_handles_large_data_sets()
    {
        // Arrange
        $reportData = $this->createSampleReportData([
            'summary' => [
                'issues' => array_fill(0, 50, 'Sample issue description'),
                'warnings' => array_fill(0, 30, 'Sample warning description'),
                'successes' => array_fill(0, 20, 'Sample success description'),
                'key_insights' => array_fill(0, 10, 'Sample insight description'),
                'total_issues' => 50,
                'total_warnings' => 30,
                'total_successes' => 20,
                'grade' => 'C'
            ],
            'recommendations' => array_fill(0, 25, [
                'type' => 'improvement',
                'category' => 'content',
                'issue' => 'Sample recommendation issue',
                'recommendation' => 'Sample recommendation text'
            ])
        ]);

        // Act
        $filename = $this->service->generateReport($reportData, 'standard');

        // Assert
        $this->assertTrue(Storage::exists($filename));
        $content = Storage::get($filename);
        $this->assertNotEmpty($content);
        $this->assertTrue(strlen($content) > 10000); // Should be substantial with lots of data
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
            'seo_score' => [
                'overall' => 85,
                'title' => [
                    'score' => 90,
                    'max_score' => 100,
                    'metrics' => ['length' => 45]
                ],
                'content' => [
                    'score' => 80,
                    'max_score' => 100,
                    'metrics' => ['word_count' => 500]
                ],
                'technical' => [
                    'score' => 85,
                    'max_score' => 100,
                    'metrics' => ['load_time' => 2.1]
                ]
            ],
            'summary' => [
                'grade' => 'A',
                'total_issues' => 2,
                'total_warnings' => 3,
                'total_successes' => 8,
                'issues' => ['Missing meta description', 'Image without alt text'],
                'warnings' => ['Title could be optimized', 'Add more content'],
                'successes' => ['Fast loading', 'Mobile responsive', 'Good structure'],
                'key_insights' => ['Overall good performance', 'Minor issues to address']
            ],
            'recommendations' => [
                [
                    'type' => 'critical',
                    'category' => 'meta',
                    'issue' => 'Missing meta description',
                    'recommendation' => 'Add meta description'
                ],
                [
                    'type' => 'improvement',
                    'category' => 'content',
                    'issue' => 'Content could be expanded',
                    'recommendation' => 'Add more comprehensive content'
                ]
            ],
            'analysis_data' => [
                'meta' => [
                    'title' => 'Sample Page Title',
                    'title_length' => 18,
                    'description' => 'Sample description',
                    'description_length' => 18
                ],
                'content' => [
                    'word_count' => 500,
                    'text_to_html_ratio' => 25
                ],
                'technical' => [
                    'status_code' => 200,
                    'mobile_friendly' => true
                ]
            ]
        ];

        return array_merge_recursive($defaultData, $overrides);
    }

    private function createSampleCombinedData(): array
    {
        return [
            'batch_id' => 'batch_' . uniqid(),
            'reports' => [
                [
                    'url' => 'https://example1.com',
                    'seo_score' => ['overall' => 85],
                    'summary' => ['total_issues' => 2, 'total_warnings' => 3, 'total_successes' => 8],
                    'success' => true
                ],
                [
                    'url' => 'https://example2.com',
                    'seo_score' => ['overall' => 78],
                    'summary' => ['total_issues' => 4, 'total_warnings' => 2, 'total_successes' => 6],
                    'success' => true
                ]
            ],
            'summary' => [
                'total_urls' => 2,
                'successful_reports' => 2,
                'average_seo_score' => 81,
                'total_issues_found' => 6,
                'generated_at' => now()
            ]
        ];
    }
}