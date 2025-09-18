<?php

namespace App\Services;

use App\Analyzers\KeywordAnalyzer;
use App\Analyzers\LinkAnalyzer;
use App\Analyzers\ReadabilityAnalyzer;
use App\DTOs\CrawlResult;
use App\Models\ContentSeoResult;
use App\Models\SeoAnalysis;
use App\Utils\TextProcessor;
use Illuminate\Support\Facades\Log;

class ContentSeoAnalyzer
{
    private KeywordAnalyzer $keywordAnalyzer;
    private ReadabilityAnalyzer $readabilityAnalyzer;
    private LinkAnalyzer $linkAnalyzer;

    public function __construct(
        KeywordAnalyzer $keywordAnalyzer,
        ReadabilityAnalyzer $readabilityAnalyzer,
        LinkAnalyzer $linkAnalyzer
    ) {
        $this->keywordAnalyzer = $keywordAnalyzer;
        $this->readabilityAnalyzer = $readabilityAnalyzer;
        $this->linkAnalyzer = $linkAnalyzer;
    }

    /**
     * Perform comprehensive content SEO analysis
     */
    public function analyze(CrawlResult $crawlResult, array $keywords = []): array
    {
        Log::info('Starting content SEO analysis', [
            'url' => $crawlResult->getUrl(),
            'keywords' => $keywords
        ]);

        try {
            // Run all content analyzers
            $keywordAnalysis = $this->keywordAnalyzer->analyze($crawlResult, $keywords);
            $readabilityAnalysis = $this->readabilityAnalyzer->analyze($crawlResult);
            $linkAnalysis = $this->linkAnalyzer->analyze($crawlResult);

            // Combine results
            $results = [
                'keyword_analysis' => $keywordAnalysis,
                'readability_analysis' => $readabilityAnalysis,
                'link_analysis' => $linkAnalysis,
                'content_summary' => $this->generateContentSummary($crawlResult, $readabilityAnalysis),
                'overall_score' => $this->calculateOverallScore($keywordAnalysis, $readabilityAnalysis, $linkAnalysis),
                'recommendations' => $this->generateRecommendations($keywordAnalysis, $readabilityAnalysis, $linkAnalysis),
            ];

            Log::info('Content SEO analysis completed successfully', [
                'url' => $crawlResult->getUrl(),
                'overall_score' => $results['overall_score']
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Content SEO analysis failed', [
                'url' => $crawlResult->getUrl(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorResult($e->getMessage());
        }
    }

    /**
     * Store content SEO analysis results in database
     */
    public function storeResults(SeoAnalysis $seoAnalysis, array $analysisResults): ContentSeoResult
    {
        try {
            // Prepare data for storage
            $keywordData = $analysisResults['keyword_analysis'] ?? [];
            $readabilityData = $analysisResults['readability_analysis'] ?? [];
            $linkData = $analysisResults['link_analysis'] ?? [];

            // Extract key metrics for storage
            $keywordDensity = $keywordData['keyword_densities'] ?? [];
            $readabilityScore = $readabilityData['flesch_reading_ease'] ?? 0;
            $wordCount = $readabilityData['text_statistics']['word_count'] ?? 0;

            // Prepare heading tags data
            $hTags = [];
            if (isset($linkData['heading_structure']['headings'])) {
                foreach ($linkData['heading_structure']['headings'] as $heading) {
                    $level = $heading['level'];
                    if (!isset($hTags["h{$level}"])) {
                        $hTags["h{$level}"] = [];
                    }
                    $hTags["h{$level}"][] = $heading['text'];
                }
            }

            // Prepare links data
            $internalLinks = [];
            $externalLinks = [];
            
            if (isset($linkData['links_analysis'])) {
                $linksAnalysis = $linkData['links_analysis'];
                
                foreach ($linksAnalysis['internal_links'] ?? [] as $link) {
                    $internalLinks[] = [
                        'url' => $link['url'],
                        'text' => $link['text'],
                        'title' => $link['title'] ?? '',
                    ];
                }

                foreach ($linksAnalysis['external_links'] ?? [] as $link) {
                    $externalLinks[] = [
                        'url' => $link['url'],
                        'text' => $link['text'],
                        'title' => $link['title'] ?? '',
                        'domain' => $link['domain'] ?? '',
                    ];
                }
            }

            // Prepare image analysis data
            $imageAnalysis = [];
            if (isset($linkData['image_analysis'])) {
                $imageAnalysis = [
                    'total_images' => $linkData['image_analysis']['total_images'] ?? 0,
                    'images_with_alt' => $linkData['image_analysis']['images_with_alt'] ?? 0,
                    'missing_alt_count' => $linkData['image_analysis']['missing_alt_count'] ?? 0,
                    'optimization_percentage' => $linkData['image_analysis']['alt_text_optimization']['optimization_percentage'] ?? 0,
                ];
            }

            // Create and save the result
            $contentResult = ContentSeoResult::create([
                'seo_analysis_id' => $seoAnalysis->id,
                'keyword_density' => $keywordDensity,
                'readability_score' => $readabilityScore,
                'h_tags' => $hTags,
                'word_count' => $wordCount,
                'internal_links' => $internalLinks,
                'external_links' => $externalLinks,
                'image_analysis' => $imageAnalysis,
            ]);

            Log::info('Content SEO results stored successfully', [
                'seo_analysis_id' => $seoAnalysis->id,
                'content_result_id' => $contentResult->id
            ]);

            return $contentResult;

        } catch (\Exception $e) {
            Log::error('Failed to store content SEO results', [
                'seo_analysis_id' => $seoAnalysis->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate content summary
     */
    private function generateContentSummary(CrawlResult $crawlResult, array $readabilityAnalysis): array
    {
        $textStats = $readabilityAnalysis['text_statistics'] ?? [];
        
        return [
            'url' => $crawlResult->getUrl(),
            'total_words' => $textStats['word_count'] ?? 0,
            'total_sentences' => $textStats['sentence_count'] ?? 0,
            'total_characters' => $textStats['character_count'] ?? 0,
            'average_sentence_length' => $textStats['avg_sentence_length'] ?? 0,
            'reading_level' => $readabilityAnalysis['readability_grade'] ?? 'Unknown',
            'language' => TextProcessor::detectLanguage($crawlResult->getContent() ?? ''),
            'content_depth' => $this->assessContentDepth($textStats),
        ];
    }

    /**
     * Assess content depth based on word count
     */
    private function assessContentDepth(array $textStats): string
    {
        $wordCount = $textStats['word_count'] ?? 0;

        if ($wordCount >= 2000) {
            return 'Comprehensive';
        } elseif ($wordCount >= 1000) {
            return 'Detailed';
        } elseif ($wordCount >= 500) {
            return 'Moderate';
        } elseif ($wordCount >= 300) {
            return 'Basic';
        } else {
            return 'Minimal';
        }
    }

    /**
     * Calculate overall content SEO score
     */
    private function calculateOverallScore(array $keywordAnalysis, array $readabilityAnalysis, array $linkAnalysis): float
    {
        $scores = [];

        // Keyword score (30% weight)
        $keywordScore = $this->calculateKeywordScore($keywordAnalysis);
        $scores['keyword'] = ['score' => $keywordScore, 'weight' => 0.30];

        // Readability score (25% weight)
        $readabilityScore = $this->calculateReadabilityScore($readabilityAnalysis);
        $scores['readability'] = ['score' => $readabilityScore, 'weight' => 0.25];

        // Link structure score (25% weight)
        $linkScore = $this->calculateLinkScore($linkAnalysis);
        $scores['links'] = ['score' => $linkScore, 'weight' => 0.25];

        // Content structure score (20% weight)
        $structureScore = $this->calculateStructureScore($linkAnalysis);
        $scores['structure'] = ['score' => $structureScore, 'weight' => 0.20];

        // Calculate weighted average
        $totalScore = 0;
        foreach ($scores as $component) {
            $totalScore += $component['score'] * $component['weight'];
        }

        return round($totalScore, 1);
    }

    /**
     * Calculate keyword optimization score
     */
    private function calculateKeywordScore(array $keywordAnalysis): float
    {
        $score = 100;

        // Check for keyword stuffing
        if ($keywordAnalysis['keyword_stuffing_risk'] ?? false) {
            $score -= 30;
        }

        // Check keyword density
        $densities = $keywordAnalysis['keyword_densities'] ?? [];
        $idealDensityCount = 0;
        foreach ($densities as $density) {
            if ($density >= 1 && $density <= 3) {
                $idealDensityCount++;
            }
        }

        if (count($densities) > 0) {
            $densityScore = ($idealDensityCount / count($densities)) * 30;
            $score = ($score * 0.7) + ($densityScore * 0.3);
        }

        // Bonus for long-tail keywords
        $longTailCount = count($keywordAnalysis['long_tail_keywords'] ?? []);
        if ($longTailCount > 0) {
            $score += min(10, $longTailCount * 2);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate readability score
     */
    private function calculateReadabilityScore(array $readabilityAnalysis): float
    {
        $fleschScore = $readabilityAnalysis['flesch_reading_ease'] ?? 0;
        
        // Convert Flesch score to 0-100 scale
        if ($fleschScore >= 60) {
            return min(100, $fleschScore);
        } elseif ($fleschScore >= 30) {
            return ($fleschScore - 30) * (60/30) + 40;
        } else {
            return max(0, $fleschScore * (40/30));
        }
    }

    /**
     * Calculate link optimization score
     */
    private function calculateLinkScore(array $linkAnalysis): float
    {
        $linksData = $linkAnalysis['links_analysis'] ?? [];
        $anchorData = $linkAnalysis['anchor_text_analysis'] ?? [];
        
        $score = 100;

        // Internal vs external link ratio
        $internalCount = $linksData['internal_count'] ?? 0;
        $externalCount = $linksData['external_count'] ?? 0;
        $totalLinks = $internalCount + $externalCount;

        if ($totalLinks > 0) {
            $internalRatio = $internalCount / $totalLinks;
            if ($internalRatio >= 0.6 && $internalRatio <= 0.8) {
                $score += 10;
            }
        }

        // Anchor text optimization
        if (isset($anchorData['optimization_score'])) {
            $score = ($score * 0.7) + ($anchorData['optimization_score'] * 0.3);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate content structure score
     */
    private function calculateStructureScore(array $linkAnalysis): float
    {
        $headingData = $linkAnalysis['heading_structure'] ?? [];
        $imageData = $linkAnalysis['image_analysis'] ?? [];
        
        $score = 0;

        // Heading structure (50 points)
        if ($headingData['has_proper_structure'] ?? false) {
            $score += 50;
        } else {
            $issueCount = count($headingData['issues'] ?? []);
            $score += max(0, 50 - ($issueCount * 10));
        }

        // Image optimization (30 points)
        $imageOptimization = $imageData['alt_text_optimization']['optimization_percentage'] ?? 0;
        $score += ($imageOptimization / 100) * 30;

        // Content organization (20 points)
        $contentStructure = $linkAnalysis['content_structure'] ?? [];
        $organizationScore = $contentStructure['content_organization']['organization_score'] ?? 0;
        $score += ($organizationScore / 100) * 20;

        return min(100, $score);
    }

    /**
     * Generate comprehensive recommendations
     */
    private function generateRecommendations(array $keywordAnalysis, array $readabilityAnalysis, array $linkAnalysis): array
    {
        $recommendations = [];

        // Keyword recommendations
        if ($keywordAnalysis['keyword_stuffing_risk'] ?? false) {
            $recommendations[] = 'Reduce keyword density to avoid keyword stuffing penalties';
        }

        if (empty($keywordAnalysis['keyword_densities'] ?? [])) {
            $recommendations[] = 'Add target keywords to improve content relevance';
        }

        // Readability recommendations
        $recommendations = array_merge($recommendations, $readabilityAnalysis['recommendations'] ?? []);

        // Link recommendations
        $linksData = $linkAnalysis['links_analysis'] ?? [];
        if (($linksData['internal_count'] ?? 0) < 3) {
            $recommendations[] = 'Add more internal links to improve site navigation and SEO';
        }

        if (($linksData['external_count'] ?? 0) > ($linksData['internal_count'] ?? 0)) {
            $recommendations[] = 'Balance external links with more internal links';
        }

        // Image recommendations
        $imageData = $linkAnalysis['image_analysis'] ?? [];
        if (($imageData['missing_alt_count'] ?? 0) > 0) {
            $recommendations[] = 'Add alt text to images for better accessibility and SEO';
        }

        // Heading recommendations
        $headingData = $linkAnalysis['heading_structure'] ?? [];
        if (!empty($headingData['issues'] ?? [])) {
            foreach ($headingData['issues'] as $issue) {
                $recommendations[] = "Heading structure: {$issue}";
            }
        }

        return array_unique($recommendations);
    }

    /**
     * Get error result structure
     */
    private function getErrorResult(string $error): array
    {
        return [
            'keyword_analysis' => [],
            'readability_analysis' => [],
            'link_analysis' => [],
            'content_summary' => [],
            'overall_score' => 0,
            'recommendations' => ["Analysis failed: {$error}"],
            'error' => $error,
        ];
    }
}