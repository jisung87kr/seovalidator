<?php

namespace App\Services\Content;

use App\Services\Quality\ContentQualityAssessor;
use App\Services\Parser\HtmlParserService;
use App\Services\Crawler\DomExtractor;
use Illuminate\Support\Facades\Log;

/**
 * Main orchestrator for content SEO analysis
 * Coordinates all content-related analysis including keyword density, readability,
 * heading structure, image optimization, link analysis, and duplicate content detection
 */
class ContentAnalyzerService
{
    public function __construct(
        private ContentQualityAssessor $contentQualityAssessor,
        private HtmlParserService $htmlParserService,
        private DomExtractor $domExtractor,
        private KeywordDensityAnalyzer $keywordDensityAnalyzer,
        private ReadabilityAnalyzer $readabilityAnalyzer,
        private HeadingHierarchyValidator $headingValidator,
        private ImageOptimizationAnalyzer $imageAnalyzer,
        private LinkAnalyzer $linkAnalyzer,
        private DuplicateContentDetector $duplicateDetector
    ) {}

    /**
     * Perform comprehensive content SEO analysis
     */
    public function analyze(string $html, string $url, array $options = []): array
    {
        Log::debug('Starting comprehensive content SEO analysis', [
            'url' => $url,
            'html_size' => strlen($html),
            'options' => $options
        ]);

        $startTime = microtime(true);

        try {
            // Parse HTML content for base data
            $parsedData = $this->htmlParserService->parse($html, $url);

            // Initialize DOM extractor
            $this->domExtractor->initialize($html, $url);

            // Extract text content for analysis
            $textContent = $this->extractTextContent($html);

            // Perform all content analyses
            $contentQuality = $this->contentQualityAssessor->assess($html, $url, $parsedData, $options);
            $keywordAnalysis = $this->keywordDensityAnalyzer->analyze($textContent, $parsedData, $options);
            $readabilityAnalysis = $this->readabilityAnalyzer->analyze($textContent, $html, $options);
            $headingAnalysis = $this->headingValidator->validate($parsedData['headings'], $options);
            $imageAnalysis = $this->imageAnalyzer->analyze($parsedData['images'], $html, $url, $options);
            $linkAnalysis = $this->linkAnalyzer->analyze($parsedData['links'], $html, $url, $options);
            $duplicateAnalysis = $this->duplicateDetector->detect($textContent, $html, $url, $options);

            // Calculate overall content SEO score
            $overallScore = $this->calculateContentSeoScore([
                'content_quality' => $contentQuality,
                'keyword_density' => $keywordAnalysis,
                'readability' => $readabilityAnalysis,
                'heading_structure' => $headingAnalysis,
                'image_optimization' => $imageAnalysis,
                'link_analysis' => $linkAnalysis,
                'duplicate_content' => $duplicateAnalysis
            ]);

            // Generate comprehensive recommendations
            $recommendations = $this->generateContentRecommendations([
                'content_quality' => $contentQuality,
                'keyword_density' => $keywordAnalysis,
                'readability' => $readabilityAnalysis,
                'heading_structure' => $headingAnalysis,
                'image_optimization' => $imageAnalysis,
                'link_analysis' => $linkAnalysis,
                'duplicate_content' => $duplicateAnalysis
            ]);

            $analysis = [
                'url' => $url,
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'content_quality' => $contentQuality,
                'keyword_analysis' => $keywordAnalysis,
                'readability_analysis' => $readabilityAnalysis,
                'heading_analysis' => $headingAnalysis,
                'image_analysis' => $imageAnalysis,
                'link_analysis' => $linkAnalysis,
                'duplicate_content_analysis' => $duplicateAnalysis,
                'recommendations' => $recommendations,
                'content_metrics' => $this->extractContentMetrics($textContent, $html, $parsedData),
                'seo_summary' => $this->generateSeoSummary($overallScore, $recommendations)
            ];

            Log::info('Content SEO analysis completed successfully', [
                'url' => $url,
                'overall_score' => $overallScore,
                'analysis_time_ms' => $analysis['analysis_duration_ms'],
                'recommendations_count' => count($recommendations)
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Content SEO analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extract clean text content from HTML
     */
    private function extractTextContent(string $html): string
    {
        // Remove script and style elements
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Strip HTML tags
        $text = strip_tags($html);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Calculate overall content SEO score
     */
    private function calculateContentSeoScore(array $analyses): array
    {
        $weights = [
            'content_quality' => 0.25,      // 25%
            'keyword_density' => 0.20,      // 20%
            'readability' => 0.15,          // 15%
            'heading_structure' => 0.15,    // 15%
            'image_optimization' => 0.10,   // 10%
            'link_analysis' => 0.10,        // 10%
            'duplicate_content' => 0.05     // 5%
        ];

        $totalScore = 0;
        $componentScores = [];

        foreach ($analyses as $type => $analysis) {
            $score = $analysis['overall_score'] ?? $analysis['score'] ?? 0;
            $weight = $weights[$type] ?? 0;
            $componentScores[$type] = $score;
            $totalScore += $score * $weight;
        }

        return [
            'overall' => round($totalScore, 1),
            'components' => $componentScores,
            'weights' => $weights,
            'grade' => $this->getContentGrade($totalScore)
        ];
    }

    /**
     * Generate comprehensive content recommendations
     */
    private function generateContentRecommendations(array $analyses): array
    {
        $recommendations = [];

        // Collect recommendations from all analyses
        foreach ($analyses as $type => $analysis) {
            if (isset($analysis['recommendations'])) {
                $recommendations = array_merge($recommendations, $analysis['recommendations']);
            }
        }

        // Sort by priority and impact
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['error' => 0, 'warning' => 1, 'suggestion' => 2];
            $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            $aPriority = $priorityOrder[$a['type']] ?? 3;
            $bPriority = $priorityOrder[$b['type']] ?? 3;

            if ($aPriority !== $bPriority) {
                return $aPriority - $bPriority;
            }

            $aImpact = $impactOrder[$a['impact']] ?? 3;
            $bImpact = $impactOrder[$b['impact']] ?? 3;

            return $aImpact - $bImpact;
        });

        return array_slice($recommendations, 0, 20); // Limit to top 20 recommendations
    }

    /**
     * Extract comprehensive content metrics
     */
    private function extractContentMetrics(string $textContent, string $html, array $parsedData): array
    {
        return [
            'word_count' => str_word_count($textContent),
            'character_count' => strlen($textContent),
            'paragraph_count' => substr_count($html, '<p'),
            'sentence_count' => preg_match_all('/[.!?]+/', $textContent),
            'heading_count' => array_sum(array_map('count', $parsedData['headings'] ?? [])),
            'image_count' => $parsedData['images']['total_count'] ?? 0,
            'link_count' => $parsedData['links']['total_count'] ?? 0,
            'reading_time_minutes' => ceil(str_word_count($textContent) / 200),
            'content_density' => strlen($textContent) > 0 ?
                round((str_word_count($textContent) / strlen($textContent)) * 100, 2) : 0
        ];
    }

    /**
     * Generate SEO summary
     */
    private function generateSeoSummary(array $overallScore, array $recommendations): array
    {
        $criticalIssues = array_filter($recommendations, fn($r) => $r['type'] === 'error');
        $warnings = array_filter($recommendations, fn($r) => $r['type'] === 'warning');
        $suggestions = array_filter($recommendations, fn($r) => $r['type'] === 'suggestion');

        return [
            'grade' => $overallScore['grade'],
            'score' => $overallScore['overall'],
            'status' => $this->getContentStatus($overallScore['overall']),
            'critical_issues_count' => count($criticalIssues),
            'warnings_count' => count($warnings),
            'suggestions_count' => count($suggestions),
            'top_priority' => $this->getTopPriority($recommendations),
            'strengths' => $this->identifyContentStrengths($overallScore['components']),
            'weaknesses' => $this->identifyContentWeaknesses($overallScore['components'])
        ];
    }

    /**
     * Get content grade based on score
     */
    private function getContentGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';
        return 'F';
    }

    /**
     * Get content status description
     */
    private function getContentStatus(float $score): string
    {
        if ($score >= 85) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 65) return 'Fair';
        if ($score >= 50) return 'Poor';
        return 'Critical';
    }

    /**
     * Get top priority recommendation
     */
    private function getTopPriority(array $recommendations): ?string
    {
        if (empty($recommendations)) {
            return null;
        }

        $critical = array_filter($recommendations, fn($r) => $r['type'] === 'error');
        if (!empty($critical)) {
            return reset($critical)['message'];
        }

        $warnings = array_filter($recommendations, fn($r) => $r['type'] === 'warning');
        if (!empty($warnings)) {
            return reset($warnings)['message'];
        }

        return reset($recommendations)['message'];
    }

    /**
     * Identify content strengths
     */
    private function identifyContentStrengths(array $components): array
    {
        $strengths = [];
        foreach ($components as $component => $score) {
            if ($score >= 80) {
                $strengths[] = ucfirst(str_replace('_', ' ', $component));
            }
        }
        return $strengths;
    }

    /**
     * Identify content weaknesses
     */
    private function identifyContentWeaknesses(array $components): array
    {
        $weaknesses = [];
        foreach ($components as $component => $score) {
            if ($score < 60) {
                $weaknesses[] = ucfirst(str_replace('_', ' ', $component));
            }
        }
        return $weaknesses;
    }
}