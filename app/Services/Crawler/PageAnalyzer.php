<?php

namespace App\Services\Crawler;

use App\Services\Crawler\DomExtractor;
use App\Services\Analysis\PerformanceAnalyzer;
use App\Services\Quality\ContentQualityAssessor;
use App\Services\Analysis\CompetitiveAnalysis;
use Illuminate\Support\Facades\Log;

/**
 * Overall page quality analysis orchestrator
 * Coordinates between different analyzers to provide comprehensive page assessment
 */
class PageAnalyzer
{
    public function __construct(
        private DomExtractor $domExtractor,
        private PerformanceAnalyzer $performanceAnalyzer,
        private ContentQualityAssessor $contentQualityAssessor,
        private CompetitiveAnalysis $competitiveAnalysis
    ) {}

    /**
     * Perform comprehensive page analysis
     */
    public function analyze(string $html, string $url, array $parsedData = [], array $options = []): array
    {
        Log::debug('Starting comprehensive page analysis', [
            'url' => $url,
            'html_size' => strlen($html),
            'options' => $options
        ]);

        $startTime = microtime(true);

        try {
            // Initialize DOM extractor with HTML content
            $this->domExtractor->initialize($html, $url);

            // Extract comprehensive DOM data
            $domData = $this->extractDomData();

            // Analyze page performance
            $performanceData = $this->performanceAnalyzer->analyze($html, $url, $domData, $options);

            // Assess content quality
            $contentQuality = $this->contentQualityAssessor->assess($html, $url, array_merge($parsedData, $domData), $options);

            // Perform competitive analysis if requested
            $competitiveData = [];
            if ($options['include_competitive'] ?? false) {
                $competitiveData = $this->competitiveAnalysis->analyze($url, array_merge($parsedData, $domData), $options);
            }

            // Calculate overall page quality score
            $qualityScore = $this->calculateOverallQualityScore($performanceData, $contentQuality, $domData);

            $analysis = [
                'url' => $url,
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'quality_score' => $qualityScore,
                'dom_analysis' => $domData,
                'performance_analysis' => $performanceData,
                'content_quality' => $contentQuality,
                'competitive_analysis' => $competitiveData,
                'recommendations' => $this->generateQualityRecommendations($performanceData, $contentQuality, $domData),
                'summary' => $this->generateAnalysisSummary($qualityScore, $performanceData, $contentQuality, $domData)
            ];

            Log::info('Page analysis completed successfully', [
                'url' => $url,
                'quality_score' => $qualityScore['overall'],
                'analysis_time_ms' => $analysis['analysis_duration_ms']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Page analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extract comprehensive DOM data using DomExtractor
     */
    private function extractDomData(): array
    {
        return [
            'tables' => $this->domExtractor->extractTables(),
            'forms' => $this->domExtractor->extractForms(),
            'multimedia' => $this->domExtractor->extractMultimedia(),
            'navigation' => $this->domExtractor->extractNavigation(),
            'accessibility' => $this->domExtractor->extractAccessibilityFeatures(),
            'performance_elements' => $this->domExtractor->extractPerformanceElements(),
            'security' => $this->domExtractor->extractSecurityElements(),
            'semantic' => $this->domExtractor->extractSemanticElements(),
            'custom_data' => $this->domExtractor->extractCustomData()
        ];
    }

    /**
     * Calculate overall page quality score from all analysis components
     */
    private function calculateOverallQualityScore(array $performance, array $contentQuality, array $domData): array
    {
        // Define weights for different quality aspects
        $weights = [
            'performance' => 0.25,     // 25%
            'content' => 0.30,         // 30%
            'accessibility' => 0.20,    // 20%
            'technical' => 0.15,       // 15%
            'semantic' => 0.10         // 10%
        ];

        // Extract component scores
        $performanceScore = $performance['overall_score'] ?? 0;
        $contentScore = $contentQuality['overall_score'] ?? 0;
        $accessibilityScore = $this->calculateAccessibilityScore($domData['accessibility']);
        $technicalScore = $this->calculateTechnicalScore($domData['performance_elements'], $domData['security']);
        $semanticScore = $domData['semantic']['semantic_score'] ?? 0;

        // Calculate weighted overall score
        $overallScore =
            ($performanceScore * $weights['performance']) +
            ($contentScore * $weights['content']) +
            ($accessibilityScore * $weights['accessibility']) +
            ($technicalScore * $weights['technical']) +
            ($semanticScore * $weights['semantic']);

        return [
            'overall' => round($overallScore, 1),
            'components' => [
                'performance' => round($performanceScore, 1),
                'content' => round($contentScore, 1),
                'accessibility' => round($accessibilityScore, 1),
                'technical' => round($technicalScore, 1),
                'semantic' => round($semanticScore, 1)
            ],
            'weights' => $weights,
            'grade' => $this->getQualityGrade($overallScore)
        ];
    }

    /**
     * Calculate accessibility score from DOM data
     */
    private function calculateAccessibilityScore(array $accessibilityData): float
    {
        $score = 0;
        $maxScore = 100;

        // ARIA labels usage (20 points)
        $ariaLabels = $accessibilityData['aria_labels'] ?? [];
        $ariaScore = min(20, ($ariaLabels['aria_label'] ?? 0) * 2);
        $score += $ariaScore;

        // Landmark usage (20 points)
        $landmarks = $accessibilityData['landmarks']['landmarks'] ?? [];
        $landmarkScore = min(20, count(array_filter($landmarks)) * 3);
        $score += $landmarkScore;

        // Heading structure (25 points)
        $headingStructure = $accessibilityData['heading_structure'] ?? [];
        $headingScore = 0;
        if ($headingStructure['has_h1'] ?? false) $headingScore += 10;
        if (!($headingStructure['multiple_h1'] ?? false)) $headingScore += 5;
        if ($headingStructure['proper_hierarchy'] ?? false) $headingScore += 10;
        $score += $headingScore;

        // Focus management (20 points)
        $focusManagement = $accessibilityData['focus_management'] ?? [];
        $focusScore = 0;
        if (($focusManagement['tabindex_positive'] ?? 0) === 0) $focusScore += 10; // No positive tabindex
        $focusScore += min(10, ($focusManagement['focusable_elements'] ?? 0) * 0.1);
        $score += $focusScore;

        // Color contrast and keyboard navigation (15 points)
        $keyboardNav = $accessibilityData['keyboard_navigation'] ?? [];
        $navScore = min(15, ($keyboardNav['skip_links_count'] ?? 0) * 5);
        $score += $navScore;

        return ($score / $maxScore) * 100;
    }

    /**
     * Calculate technical score from performance and security data
     */
    private function calculateTechnicalScore(array $performanceElements, array $security): float
    {
        $score = 0;
        $maxScore = 100;

        // Performance optimizations (50 points)
        $lazyLoading = $performanceElements['lazy_loading'] ?? [];
        $preloadHints = $performanceElements['preload_hints'] ?? [];
        $resourceHints = $performanceElements['resource_hints'] ?? [];

        $perfScore = 0;
        $perfScore += min(15, ($lazyLoading['img_lazy'] ?? 0) * 2); // Lazy loading images
        $perfScore += min(10, ($preloadHints['preload'] ?? 0) * 3); // Preload hints
        $perfScore += min(10, ($resourceHints['preconnect'] ?? 0) * 3); // Preconnect hints
        $perfScore += min(15, 15 - (($performanceElements['third_party_scripts']['total_count'] ?? 0) * 2)); // Fewer third-party scripts

        $score += $perfScore;

        // Security features (50 points)
        $integrityAttrs = $security['integrity_attributes'] ?? [];
        $externalLinks = $security['external_links_security'] ?? [];
        $formSecurity = $security['form_security'] ?? [];

        $secScore = 0;
        $secScore += min(15, ($integrityAttrs['script_integrity'] ?? 0) * 3); // Script integrity
        $secScore += min(15, ($integrityAttrs['link_integrity'] ?? 0) * 3); // Link integrity
        $secScore += min(10, 10 - ($externalLinks['without_noopener'] ?? 0)); // External link security
        $secScore += min(10, 10 - ($formSecurity['forms_without_csrf'] ?? 0)); // Form security

        $score += $secScore;

        return ($score / $maxScore) * 100;
    }

    /**
     * Get quality grade based on overall score
     */
    private function getQualityGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Generate quality-focused recommendations
     */
    private function generateQualityRecommendations(array $performance, array $contentQuality, array $domData): array
    {
        $recommendations = [];

        // Performance recommendations
        if (isset($performance['recommendations'])) {
            $recommendations = array_merge($recommendations, $performance['recommendations']);
        }

        // Content quality recommendations
        if (isset($contentQuality['recommendations'])) {
            $recommendations = array_merge($recommendations, $contentQuality['recommendations']);
        }

        // Accessibility recommendations
        $accessibility = $domData['accessibility'] ?? [];
        if (!($accessibility['heading_structure']['has_h1'] ?? false)) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'accessibility',
                'message' => 'Missing H1 heading',
                'impact' => 'high',
                'fix' => 'Add a descriptive H1 heading to improve page structure and accessibility'
            ];
        }

        if (($accessibility['landmarks']['total_landmarks'] ?? 0) < 3) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'accessibility',
                'message' => 'Limited use of semantic landmarks',
                'impact' => 'medium',
                'fix' => 'Add semantic landmarks (main, nav, header, footer, aside) to improve page structure'
            ];
        }

        // Security recommendations
        $security = $domData['security'] ?? [];
        if (($security['external_links_security']['without_noopener'] ?? 0) > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'security',
                'message' => 'External links missing rel="noopener"',
                'impact' => 'medium',
                'fix' => 'Add rel="noopener noreferrer" to external links that open in new tabs'
            ];
        }

        // Technical recommendations
        $performanceElements = $domData['performance_elements'] ?? [];
        if (($performanceElements['lazy_loading']['img_lazy'] ?? 0) === 0 &&
            isset($domData['images']) && ($domData['images']['total_count'] ?? 0) > 5) {
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'performance',
                'message' => 'Consider implementing lazy loading for images',
                'impact' => 'medium',
                'fix' => 'Add loading="lazy" attribute to images below the fold to improve page load performance'
            ];
        }

        return $recommendations;
    }

    /**
     * Generate analysis summary
     */
    private function generateAnalysisSummary(array $qualityScore, array $performance, array $contentQuality, array $domData): array
    {
        $strengths = [];
        $weaknesses = [];
        $priorities = [];

        // Identify strengths
        if ($qualityScore['components']['performance'] >= 80) {
            $strengths[] = 'Excellent page performance';
        }
        if ($qualityScore['components']['content'] >= 80) {
            $strengths[] = 'High-quality content';
        }
        if ($qualityScore['components']['accessibility'] >= 80) {
            $strengths[] = 'Good accessibility features';
        }
        if ($qualityScore['components']['semantic'] >= 80) {
            $strengths[] = 'Proper semantic HTML usage';
        }

        // Identify weaknesses
        if ($qualityScore['components']['performance'] < 60) {
            $weaknesses[] = 'Poor page performance';
            $priorities[] = 'Optimize page performance';
        }
        if ($qualityScore['components']['content'] < 60) {
            $weaknesses[] = 'Low content quality';
            $priorities[] = 'Improve content quality and structure';
        }
        if ($qualityScore['components']['accessibility'] < 60) {
            $weaknesses[] = 'Limited accessibility features';
            $priorities[] = 'Enhance accessibility compliance';
        }
        if ($qualityScore['components']['technical'] < 60) {
            $weaknesses[] = 'Technical implementation issues';
            $priorities[] = 'Address technical SEO issues';
        }

        return [
            'overall_assessment' => $this->getOverallAssessment($qualityScore['overall']),
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'priority_actions' => array_slice($priorities, 0, 3), // Top 3 priorities
            'key_metrics' => [
                'quality_grade' => $qualityScore['grade'],
                'performance_score' => $qualityScore['components']['performance'],
                'content_score' => $qualityScore['components']['content'],
                'accessibility_score' => $qualityScore['components']['accessibility'],
                'technical_score' => $qualityScore['components']['technical']
            ]
        ];
    }

    /**
     * Get overall assessment text based on score
     */
    private function getOverallAssessment(float $score): string
    {
        if ($score >= 90) {
            return 'Excellent - This page demonstrates outstanding quality across all areas';
        } elseif ($score >= 80) {
            return 'Very Good - This page has high quality with minor areas for improvement';
        } elseif ($score >= 70) {
            return 'Good - This page has solid quality but has room for meaningful improvements';
        } elseif ($score >= 60) {
            return 'Fair - This page meets basic standards but needs significant improvements';
        } elseif ($score >= 50) {
            return 'Poor - This page has major quality issues that need immediate attention';
        } else {
            return 'Critical - This page has severe quality issues requiring comprehensive fixes';
        }
    }
}