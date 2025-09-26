<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * SEO Recommendation Engine
 *
 * Generates actionable SEO improvement recommendations based on analysis results,
 * competitive data, and industry best practices.
 */
class RecommendationEngine
{
    private SeoMetrics $seoMetrics;

    /**
     * Recommendation priority levels
     */
    private array $priorityLevels = [
        'critical' => 1,    // Must fix - major SEO impact
        'high' => 2,        // Should fix soon - significant impact
        'medium' => 3,      // Improvement opportunity - moderate impact
        'low' => 4,         // Nice to have - minimal impact
        'optional' => 5     // Enhancement - very low impact
    ];

    /**
     * Recommendation categories
     */
    private array $categories = [
        'technical' => 'Technical SEO',
        'content' => 'Content Optimization',
        'on_page' => 'On-Page SEO',
        'performance' => 'Site Performance',
        'mobile' => 'Mobile Optimization',
        'accessibility' => 'Accessibility',
        'social' => 'Social Media Integration',
        'structured_data' => 'Structured Data',
        'user_experience' => 'User Experience'
    ];

    /**
     * Impact estimation multipliers
     */
    private array $impactMultipliers = [
        'title' => 2.5,
        'meta_description' => 1.8,
        'headings' => 2.0,
        'content' => 2.2,
        'technical' => 1.5,
        'performance' => 1.7,
        'mobile' => 1.9,
        'images' => 1.3,
        'links' => 1.4,
        'social_media' => 1.1,
        'structured_data' => 1.2
    ];

    public function __construct(SeoMetrics $seoMetrics)
    {
        $this->seoMetrics = $seoMetrics;
    }

    /**
     * Generate comprehensive SEO recommendations
     */
    public function generateRecommendations(array $analysisResults, array $context = []): array
    {
        Log::debug('Generating SEO recommendations');

        try {
            $recommendations = [];

            // Process each category of analysis results
            foreach ($analysisResults['category_scores'] ?? [] as $category => $scoreData) {
                $categoryRecommendations = $this->generateCategoryRecommendations(
                    $category,
                    $scoreData,
                    $context
                );

                $recommendations = array_merge($recommendations, $categoryRecommendations);
            }

            // Add cross-category recommendations
            $crossCategoryRecs = $this->generateCrossCategoryRecommendations($analysisResults, $context);
            $recommendations = array_merge($recommendations, $crossCategoryRecs);

            // Prioritize and rank recommendations
            $prioritizedRecommendations = $this->prioritizeRecommendations($recommendations, $context);

            // Add implementation details and effort estimates
            $detailedRecommendations = $this->addImplementationDetails($prioritizedRecommendations, $context);

            // Group recommendations by priority and category
            $groupedRecommendations = $this->groupRecommendations($detailedRecommendations);

            return [
                'recommendations' => $detailedRecommendations,
                'grouped_recommendations' => $groupedRecommendations,
                'summary' => $this->generateRecommendationSummary($detailedRecommendations),
                'quick_wins' => $this->identifyQuickWins($detailedRecommendations),
                'long_term_goals' => $this->identifyLongTermGoals($detailedRecommendations),
                'generated_at' => now()->toISOString(),
                'total_recommendations' => count($detailedRecommendations)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate SEO recommendations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to generate recommendations: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate competitive recommendations based on competitor analysis
     */
    public function generateCompetitiveRecommendations(array $siteAnalysis, array $competitorAnalysis): array
    {
        $recommendations = [];

        foreach ($competitorAnalysis as $competitor => $analysis) {
            $competitorRecs = $this->compareWithCompetitor($siteAnalysis, $analysis, $competitor);
            $recommendations = array_merge($recommendations, $competitorRecs);
        }

        return [
            'competitive_recommendations' => $recommendations,
            'competitive_summary' => $this->generateCompetitiveSummary($recommendations),
            'opportunities' => $this->identifyCompetitiveOpportunities($siteAnalysis, $competitorAnalysis)
        ];
    }

    /**
     * Generate industry-specific recommendations
     */
    public function generateIndustryRecommendations(array $analysisResults, string $industry): array
    {
        $industryWeights = $this->seoMetrics->getIndustryWeights($industry);
        $recommendations = [];

        // Focus on industry-specific high-weight categories
        foreach ($industryWeights as $category => $weight) {
            if ($weight > 15) { // High importance categories
                $categoryScore = $analysisResults['category_scores'][$category] ?? [];
                $industryRecs = $this->generateIndustryCategoryRecommendations($category, $categoryScore, $industry);
                $recommendations = array_merge($recommendations, $industryRecs);
            }
        }

        return [
            'industry_recommendations' => $recommendations,
            'industry' => $industry,
            'focus_areas' => $this->getIndustryFocusAreas($industry),
            'industry_benchmarks' => $this->getIndustryBenchmarks($industry)
        ];
    }

    /**
     * Generate category-specific recommendations
     */
    private function generateCategoryRecommendations(string $category, array $scoreData, array $context): array
    {
        $recommendations = [];
        $score = $scoreData['score'] ?? 0;
        $issues = $scoreData['issues'] ?? [];
        $metrics = $scoreData['metrics'] ?? [];

        // Generate recommendations based on category
        switch ($category) {
            case 'title':
                $recommendations = $this->generateTitleRecommendations($scoreData, $context);
                break;

            case 'meta_description':
                $recommendations = $this->generateMetaDescriptionRecommendations($scoreData, $context);
                break;

            case 'headings':
                $recommendations = $this->generateHeadingRecommendations($scoreData, $context);
                break;

            case 'content':
                $recommendations = $this->generateContentRecommendations($scoreData, $context);
                break;

            case 'images':
                $recommendations = $this->generateImageRecommendations($scoreData, $context);
                break;

            case 'links':
                $recommendations = $this->generateLinkRecommendations($scoreData, $context);
                break;

            case 'technical':
                $recommendations = $this->generateTechnicalRecommendations($scoreData, $context);
                break;

            case 'performance':
                $recommendations = $this->generatePerformanceRecommendations($scoreData, $context);
                break;

            default:
                $recommendations = $this->generateGenericRecommendations($category, $scoreData, $context);
        }

        return $recommendations;
    }

    /**
     * Generate title-specific recommendations
     */
    private function generateTitleRecommendations(array $scoreData, array $context): array
    {
        $recommendations = [];
        $issues = $scoreData['issues'] ?? [];
        $metrics = $scoreData['metrics'] ?? [];
        $score = $scoreData['score'] ?? 0;

        // Length optimization
        $length = $metrics['length'] ?? 0;
        if ($length === 0) {
            $recommendations[] = $this->createRecommendation(
                'Add Title Tag',
                'Create a descriptive title tag for your page',
                'critical',
                'on_page',
                [
                    'action' => 'Add <title> tag to HTML head',
                    'target_length' => '30-60 characters',
                    'examples' => [
                        'Product Page: "Premium Wireless Headphones - Brand Name"',
                        'Blog Post: "10 SEO Tips for Better Rankings | Blog Name"',
                        'Service Page: "Professional Web Design Services | Company"'
                    ]
                ],
                60, // High impact
                'easy',
                30 // 30 minutes
            );
        } elseif ($length < 30) {
            $recommendations[] = $this->createRecommendation(
                'Expand Title Length',
                'Your title is too short. Expand it to 30-60 characters for better visibility in search results.',
                'high',
                'on_page',
                [
                    'current_length' => $length,
                    'target_length' => '30-60 characters',
                    'suggestion' => 'Add descriptive words or your brand name'
                ],
                40,
                'easy',
                15
            );
        } elseif ($length > 60) {
            $recommendations[] = $this->createRecommendation(
                'Shorten Title Length',
                'Your title may be truncated in search results. Shorten it to under 60 characters.',
                'medium',
                'on_page',
                [
                    'current_length' => $length,
                    'target_length' => '30-60 characters',
                    'suggestion' => 'Remove less important words or shorten phrases'
                ],
                30,
                'easy',
                10
            );
        }

        // Keyword optimization
        if (isset($context['target_keywords']) && !empty($context['target_keywords'])) {
            $keywordDensity = $metrics['keyword_density'] ?? 0;
            if ($keywordDensity === 0) {
                $recommendations[] = $this->createRecommendation(
                    'Include Target Keywords',
                    'Add your primary keywords to the title tag for better relevance.',
                    'high',
                    'on_page',
                    [
                        'target_keywords' => $context['target_keywords'],
                        'placement' => 'Include primary keyword near the beginning of the title',
                        'best_practice' => 'Use keywords naturally, avoid stuffing'
                    ],
                    50,
                    'medium',
                    20
                );
            }
        }

        // Brand optimization
        if (!($metrics['has_brand'] ?? false)) {
            $recommendations[] = $this->createRecommendation(
                'Add Brand Name',
                'Include your brand name in the title for better brand recognition.',
                'low',
                'on_page',
                [
                    'placement' => 'Add brand name at the end: "Page Title | Brand Name"',
                    'separator' => 'Use | or - to separate content from brand',
                    'benefit' => 'Improves brand visibility and click-through rates'
                ],
                15,
                'easy',
                5
            );
        }

        return $recommendations;
    }

    /**
     * Generate meta description recommendations
     */
    private function generateMetaDescriptionRecommendations(array $scoreData, array $context): array
    {
        $recommendations = [];
        $metrics = $scoreData['metrics'] ?? [];
        $length = $metrics['length'] ?? 0;

        if ($length === 0) {
            $recommendations[] = $this->createRecommendation(
                'Add Meta Description',
                'Create a compelling meta description to improve click-through rates from search results.',
                'high',
                'on_page',
                [
                    'target_length' => '120-160 characters',
                    'elements' => [
                        'Summarize page content',
                        'Include primary keywords',
                        'Add a call-to-action',
                        'Make it compelling and unique'
                    ]
                ],
                45,
                'easy',
                20
            );
        } elseif ($length < 120) {
            $recommendations[] = $this->createRecommendation(
                'Expand Meta Description',
                'Your meta description is too short. Expand it to 120-160 characters to provide more context.',
                'medium',
                'on_page',
                [
                    'current_length' => $length,
                    'target_length' => '120-160 characters',
                    'additions' => ['More details about benefits', 'Call-to-action phrase', 'Unique value proposition']
                ],
                25,
                'easy',
                15
            );
        }

        return $recommendations;
    }

    /**
     * Generate content recommendations
     */
    private function generateContentRecommendations(array $scoreData, array $context): array
    {
        $recommendations = [];
        $metrics = $scoreData['metrics'] ?? [];
        $wordCount = $metrics['word_count'] ?? 0;

        if ($wordCount < 300) {
            $recommendations[] = $this->createRecommendation(
                'Increase Content Length',
                'Add more content to reach at least 300 words for better SEO value.',
                'high',
                'content',
                [
                    'current_word_count' => $wordCount,
                    'target_word_count' => '300+ words',
                    'suggestions' => [
                        'Add more detailed explanations',
                        'Include relevant examples',
                        'Expand on benefits and features',
                        'Add FAQ section'
                    ]
                ],
                40,
                'medium',
                120
            );
        }

        // Text-to-HTML ratio
        $textHtmlRatio = $metrics['text_to_html_ratio'] ?? 0;
        if ($textHtmlRatio < 15) {
            $recommendations[] = $this->createRecommendation(
                'Improve Content Density',
                'Increase the ratio of text content to HTML code for better SEO.',
                'medium',
                'technical',
                [
                    'current_ratio' => $textHtmlRatio . '%',
                    'target_ratio' => '15%+',
                    'actions' => [
                        'Remove unnecessary HTML elements',
                        'Add more text content',
                        'Optimize CSS and JavaScript',
                        'Use semantic HTML'
                    ]
                ],
                30,
                'medium',
                90
            );
        }

        return $recommendations;
    }

    /**
     * Generate image optimization recommendations
     */
    private function generateImageRecommendations(array $scoreData, array $context): array
    {
        $recommendations = [];
        $metrics = $scoreData['metrics'] ?? [];
        $issues = $scoreData['issues'] ?? [];

        $totalImages = $metrics['total_images'] ?? 0;
        $withoutAlt = $metrics['without_alt'] ?? 0;

        if ($withoutAlt > 0) {
            $recommendations[] = $this->createRecommendation(
                'Add Missing Alt Text',
                "Add descriptive alt text to {$withoutAlt} images missing alt attributes.",
                'high',
                'accessibility',
                [
                    'affected_images' => $withoutAlt,
                    'total_images' => $totalImages,
                    'guidelines' => [
                        'Describe what you see in the image',
                        'Keep descriptions concise but descriptive',
                        'Include keywords naturally when relevant',
                        'Use empty alt="" for decorative images'
                    ]
                ],
                35,
                'easy',
                $withoutAlt * 2 // 2 minutes per image
            );
        }

        return $recommendations;
    }

    /**
     * Generate cross-category recommendations
     */
    private function generateCrossCategoryRecommendations(array $analysisResults, array $context): array
    {
        $recommendations = [];
        $overallScore = $analysisResults['overall_score'] ?? 0;

        // Overall site health recommendations
        if ($overallScore < 60) {
            $recommendations[] = $this->createRecommendation(
                'Comprehensive SEO Audit Needed',
                'Your site has multiple SEO issues that need immediate attention.',
                'critical',
                'technical',
                [
                    'overall_score' => $overallScore,
                    'focus_areas' => $this->identifyLowestScoringCategories($analysisResults),
                    'approach' => 'Start with technical issues, then move to content optimization'
                ],
                80,
                'hard',
                480 // 8 hours
            );
        }

        // Mobile optimization check
        $this->addMobileOptimizationRecommendations($recommendations, $analysisResults, $context);

        // Performance optimization
        $this->addPerformanceOptimizationRecommendations($recommendations, $analysisResults, $context);

        return $recommendations;
    }

    /**
     * Create a recommendation object
     */
    private function createRecommendation(
        string $title,
        string $description,
        string $priority,
        string $category,
        array $details,
        int $impact,
        string $difficulty,
        int $estimatedMinutes
    ): array {
        return [
            'id' => uniqid('rec_', true),
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'priority_score' => $this->priorityLevels[$priority] ?? 5,
            'category' => $category,
            'category_name' => $this->categories[$category] ?? 'General',
            'impact_score' => $impact,
            'difficulty' => $difficulty,
            'estimated_time_minutes' => $estimatedMinutes,
            'estimated_time_human' => $this->formatTimeEstimate($estimatedMinutes),
            'details' => $details,
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * Prioritize recommendations based on impact and difficulty
     */
    private function prioritizeRecommendations(array $recommendations, array $context): array
    {
        usort($recommendations, function ($a, $b) {
            // First sort by priority level
            $priorityDiff = $a['priority_score'] - $b['priority_score'];
            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            // Then by impact score (higher is better)
            $impactDiff = $b['impact_score'] - $a['impact_score'];
            if ($impactDiff !== 0) {
                return $impactDiff;
            }

            // Finally by difficulty (easier first)
            $difficultyOrder = ['easy' => 1, 'medium' => 2, 'hard' => 3];
            return ($difficultyOrder[$a['difficulty']] ?? 2) - ($difficultyOrder[$b['difficulty']] ?? 2);
        });

        return $recommendations;
    }

    /**
     * Add implementation details to recommendations
     */
    private function addImplementationDetails(array $recommendations, array $context): array
    {
        foreach ($recommendations as &$recommendation) {
            $recommendation['implementation'] = $this->getImplementationDetails($recommendation);
            $recommendation['roi_estimate'] = $this->calculateROIEstimate($recommendation);
            $recommendation['dependencies'] = $this->identifyDependencies($recommendation, $recommendations);
        }

        return $recommendations;
    }

    /**
     * Group recommendations by various criteria
     */
    private function groupRecommendations(array $recommendations): array
    {
        $grouped = [
            'by_priority' => [],
            'by_category' => [],
            'by_difficulty' => []
        ];

        foreach ($recommendations as $recommendation) {
            // Group by priority
            $priority = $recommendation['priority'];
            $grouped['by_priority'][$priority][] = $recommendation;

            // Group by category
            $category = $recommendation['category'];
            $grouped['by_category'][$category][] = $recommendation;

            // Group by difficulty
            $difficulty = $recommendation['difficulty'];
            $grouped['by_difficulty'][$difficulty][] = $recommendation;
        }

        return $grouped;
    }

    /**
     * Generate recommendation summary
     */
    private function generateRecommendationSummary(array $recommendations): array
    {
        $summary = [
            'total_recommendations' => count($recommendations),
            'by_priority' => [],
            'by_category' => [],
            'estimated_total_time_hours' => 0,
            'potential_impact_score' => 0
        ];

        foreach ($recommendations as $recommendation) {
            $priority = $recommendation['priority'];
            $category = $recommendation['category'];

            $summary['by_priority'][$priority] = ($summary['by_priority'][$priority] ?? 0) + 1;
            $summary['by_category'][$category] = ($summary['by_category'][$category] ?? 0) + 1;
            $summary['estimated_total_time_hours'] += $recommendation['estimated_time_minutes'] / 60;
            $summary['potential_impact_score'] += $recommendation['impact_score'];
        }

        $summary['estimated_total_time_hours'] = round($summary['estimated_total_time_hours'], 1);

        return $summary;
    }

    /**
     * Identify quick wins (high impact, low effort)
     */
    private function identifyQuickWins(array $recommendations): array
    {
        return array_filter($recommendations, function ($recommendation) {
            return $recommendation['impact_score'] >= 30 &&
                   $recommendation['difficulty'] === 'easy' &&
                   $recommendation['estimated_time_minutes'] <= 60;
        });
    }

    /**
     * Identify long-term goals
     */
    private function identifyLongTermGoals(array $recommendations): array
    {
        return array_filter($recommendations, function ($recommendation) {
            return $recommendation['estimated_time_minutes'] > 240 || // More than 4 hours
                   $recommendation['difficulty'] === 'hard';
        });
    }

    /**
     * Helper methods
     */

    private function formatTimeEstimate(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours === 1 ? "1 hour" : "{$hours} hours";
        }

        return $hours === 1 ? "1 hour {$remainingMinutes} minutes" : "{$hours} hours {$remainingMinutes} minutes";
    }

    private function getImplementationDetails(array $recommendation): array
    {
        // This would return specific implementation steps based on recommendation type
        return [
            'steps' => ['Step-by-step implementation would be here'],
            'resources' => ['Links to relevant documentation'],
            'tools' => ['Recommended tools for implementation']
        ];
    }

    private function calculateROIEstimate(array $recommendation): array
    {
        // Simplified ROI calculation
        $impact = $recommendation['impact_score'];
        $effort = $recommendation['estimated_time_minutes'];

        $roi = $effort > 0 ? round(($impact / $effort) * 100, 2) : 0;

        return [
            'score' => $roi,
            'level' => $roi > 50 ? 'high' : ($roi > 25 ? 'medium' : 'low')
        ];
    }

    private function identifyDependencies(array $recommendation, array $allRecommendations): array
    {
        // This would identify if a recommendation depends on others being completed first
        return [];
    }

    private function identifyLowestScoringCategories(array $analysisResults): array
    {
        $categoryScores = $analysisResults['category_scores'] ?? [];

        uasort($categoryScores, function ($a, $b) {
            return ($a['score'] ?? 0) - ($b['score'] ?? 0);
        });

        return array_slice(array_keys($categoryScores), 0, 3); // Top 3 lowest scoring
    }

    private function addMobileOptimizationRecommendations(array &$recommendations, array $analysisResults, array $context): void
    {
        // Add mobile-specific recommendations based on analysis
    }

    private function addPerformanceOptimizationRecommendations(array &$recommendations, array $analysisResults, array $context): void
    {
        // Add performance-specific recommendations based on analysis
    }

    private function compareWithCompetitor(array $siteAnalysis, array $competitorAnalysis, string $competitor): array
    {
        $recommendations = [];
        // Compare metrics and generate competitive recommendations
        return $recommendations;
    }

    private function generateCompetitiveSummary(array $recommendations): array
    {
        return [
            'total_opportunities' => count($recommendations),
            'competitive_advantages' => [],
            'gaps_to_address' => []
        ];
    }

    private function identifyCompetitiveOpportunities(array $siteAnalysis, array $competitorAnalysis): array
    {
        return [];
    }

    private function generateIndustryCategoryRecommendations(string $category, array $scoreData, string $industry): array
    {
        return [];
    }

    private function getIndustryFocusAreas(string $industry): array
    {
        $focusAreas = [
            'e-commerce' => ['Product images', 'Schema markup', 'Site speed', 'Mobile experience'],
            'blog' => ['Content quality', 'Heading structure', 'Social sharing', 'Internal linking'],
            'local-business' => ['Local schema', 'NAP consistency', 'Google My Business', 'Local keywords'],
            'news' => ['Site speed', 'Article schema', 'Social sharing', 'Mobile optimization']
        ];

        return $focusAreas[$industry] ?? ['Content quality', 'Technical SEO', 'User experience'];
    }

    private function getIndustryBenchmarks(string $industry): array
    {
        return [
            'average_score' => 75,
            'top_10_percent_score' => 90,
            'key_metrics' => []
        ];
    }

    private function generateTechnicalRecommendations(array $scoreData, array $context): array
    {
        return [];
    }

    private function generatePerformanceRecommendations(array $scoreData, array $context): array
    {
        return [];
    }

    private function generateHeadingRecommendations(array $scoreData, array $context): array
    {
        return [];
    }

    private function generateLinkRecommendations(array $scoreData, array $context): array
    {
        return [];
    }

    private function generateGenericRecommendations(string $category, array $scoreData, array $context): array
    {
        return [];
    }
}