<?php

namespace App\Services\Quality;

use Illuminate\Support\Facades\Log;

/**
 * Content quality evaluation and scoring service
 * Assesses content quality across multiple dimensions and provides detailed scoring
 */
class ContentQualityAssessor
{
    /**
     * Assess content quality across multiple dimensions
     */
    public function assess(string $html, string $url, array $parsedData = [], array $options = []): array
    {
        Log::debug('Starting content quality assessment', [
            'url' => $url,
            'html_size' => strlen($html)
        ]);

        $startTime = microtime(true);

        try {
            // Extract content data
            $content = $parsedData['content'] ?? [];
            $meta = $parsedData['meta'] ?? [];
            $headings = $parsedData['headings'] ?? [];
            $images = $parsedData['images'] ?? [];
            $links = $parsedData['links'] ?? [];

            // Assess different quality dimensions
            $readability = $this->assessReadability($content, $html);
            $structure = $this->assessContentStructure($headings, $content, $html);
            $completeness = $this->assessContentCompleteness($meta, $content, $images);
            $engagement = $this->assessContentEngagement($content, $links, $html);
            $originality = $this->assessContentOriginality($content, $html);
            $relevance = $this->assessContentRelevance($meta, $content, $headings);
            $technicalQuality = $this->assessTechnicalQuality($html, $parsedData);
            $userExperience = $this->assessUserExperience($content, $structure, $html);

            // Calculate overall content quality score
            $overallScore = $this->calculateOverallContentScore([
                'readability' => $readability,
                'structure' => $structure,
                'completeness' => $completeness,
                'engagement' => $engagement,
                'originality' => $originality,
                'relevance' => $relevance,
                'technical_quality' => $technicalQuality,
                'user_experience' => $userExperience
            ]);

            // Generate quality recommendations
            $recommendations = $this->generateQualityRecommendations([
                'readability' => $readability,
                'structure' => $structure,
                'completeness' => $completeness,
                'engagement' => $engagement,
                'originality' => $originality,
                'relevance' => $relevance,
                'technical_quality' => $technicalQuality,
                'user_experience' => $userExperience
            ]);

            $assessment = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'quality_dimensions' => [
                    'readability' => $readability,
                    'structure' => $structure,
                    'completeness' => $completeness,
                    'engagement' => $engagement,
                    'originality' => $originality,
                    'relevance' => $relevance,
                    'technical_quality' => $technicalQuality,
                    'user_experience' => $userExperience
                ],
                'recommendations' => $recommendations,
                'quality_insights' => $this->generateQualityInsights($overallScore, $readability, $structure, $engagement),
                'content_metrics' => $this->extractContentMetrics($content, $html),
                'improvement_priority' => $this->identifyImprovementPriorities($overallScore, [
                    'readability' => $readability,
                    'structure' => $structure,
                    'completeness' => $completeness,
                    'engagement' => $engagement
                ])
            ];

            Log::info('Content quality assessment completed', [
                'url' => $url,
                'overall_score' => $overallScore,
                'top_strength' => $this->getTopStrength($assessment['quality_dimensions']),
                'main_weakness' => $this->getMainWeakness($assessment['quality_dimensions'])
            ]);

            return $assessment;

        } catch (\Exception $e) {
            Log::error('Content quality assessment failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Assess content readability
     */
    private function assessReadability(array $content, string $html): array
    {
        $text = $this->extractPlainText($html);
        $wordCount = $content['word_count'] ?? $this->countWords($text);
        $sentenceCount = $this->countSentences($text);
        $paragraphCount = $content['paragraph_count'] ?? $this->countParagraphs($html);

        // Calculate readability metrics
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;
        $avgSentencesPerParagraph = $paragraphCount > 0 ? $sentenceCount / $paragraphCount : 0;
        $avgWordsPerParagraph = $paragraphCount > 0 ? $wordCount / $paragraphCount : 0;

        // Calculate Flesch Reading Ease approximation
        $fleschScore = $this->calculateFleschScore($text, $wordCount, $sentenceCount);

        // Calculate readability score (0-100)
        $score = 0;

        // Sentence length scoring (30 points)
        if ($avgWordsPerSentence <= 15) $score += 30;
        elseif ($avgWordsPerSentence <= 20) $score += 20;
        elseif ($avgWordsPerSentence <= 25) $score += 10;

        // Paragraph structure scoring (25 points)
        if ($avgWordsPerParagraph >= 50 && $avgWordsPerParagraph <= 150) $score += 25;
        elseif ($avgWordsPerParagraph >= 30 && $avgWordsPerParagraph <= 200) $score += 15;

        // Flesch score integration (25 points)
        if ($fleschScore >= 70) $score += 25;
        elseif ($fleschScore >= 60) $score += 20;
        elseif ($fleschScore >= 50) $score += 15;
        elseif ($fleschScore >= 30) $score += 10;

        // Content length appropriateness (20 points)
        if ($wordCount >= 300 && $wordCount <= 2000) $score += 20;
        elseif ($wordCount >= 150 && $wordCount <= 3000) $score += 15;
        elseif ($wordCount >= 100) $score += 10;

        return [
            'score' => round($score, 1),
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'paragraph_count' => $paragraphCount,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'avg_sentences_per_paragraph' => round($avgSentencesPerParagraph, 1),
            'avg_words_per_paragraph' => round($avgWordsPerParagraph, 1),
            'flesch_reading_ease' => round($fleschScore, 1),
            'reading_level' => $this->getReadingLevel($fleschScore),
            'complexity_indicators' => $this->analyzeComplexity($text),
            'readability_issues' => $this->identifyReadabilityIssues($avgWordsPerSentence, $avgWordsPerParagraph, $fleschScore)
        ];
    }

    /**
     * Assess content structure quality
     */
    private function assessContentStructure(array $headings, array $content, string $html): array
    {
        // Analyze heading hierarchy
        $headingAnalysis = $this->analyzeHeadingHierarchy($headings);

        // Count structural elements
        $listCount = $this->countLists($html);
        $tableCount = $this->countTables($html);
        $blockquoteCount = $this->countBlockquotes($html);

        // Analyze content organization
        $organizationScore = $this->analyzeContentOrganization($html, $headings);

        // Calculate structure score (0-100)
        $score = 0;

        // Heading structure (40 points)
        $score += $headingAnalysis['score'] * 0.4;

        // Content organization (30 points)
        $score += $organizationScore * 0.3;

        // Use of structural elements (30 points)
        $structuralElementsScore = 0;
        if ($listCount > 0) $structuralElementsScore += 10;
        if ($tableCount > 0) $structuralElementsScore += 10;
        if ($blockquoteCount > 0) $structuralElementsScore += 5;

        // Bonus for good balance
        $totalElements = $listCount + $tableCount + $blockquoteCount;
        if ($totalElements >= 2) $structuralElementsScore += 5;

        $score += $structuralElementsScore;

        return [
            'score' => round($score, 1),
            'heading_analysis' => $headingAnalysis,
            'structural_elements' => [
                'lists' => $listCount,
                'tables' => $tableCount,
                'blockquotes' => $blockquoteCount,
                'total' => $totalElements
            ],
            'organization_score' => round($organizationScore, 1),
            'structure_issues' => $this->identifyStructureIssues($headingAnalysis, $organizationScore, $totalElements),
            'content_flow' => $this->analyzeContentFlow($html, $headings)
        ];
    }

    /**
     * Assess content completeness
     */
    private function assessContentCompleteness(array $meta, array $content, array $images): array
    {
        $score = 0;

        // Meta completeness (25 points)
        $metaScore = 0;
        if (!empty($meta['title'])) $metaScore += 8;
        if (!empty($meta['description'])) $metaScore += 8;
        if (!empty($meta['keywords'])) $metaScore += 4;
        if (!empty($meta['author'])) $metaScore += 3;
        if (!empty($meta['canonical'])) $metaScore += 2;
        $score += $metaScore;

        // Content depth (35 points)
        $wordCount = $content['word_count'] ?? 0;
        $contentDepthScore = 0;
        if ($wordCount >= 1000) $contentDepthScore = 35;
        elseif ($wordCount >= 500) $contentDepthScore = 25;
        elseif ($wordCount >= 300) $contentDepthScore = 20;
        elseif ($wordCount >= 150) $contentDepthScore = 15;
        elseif ($wordCount >= 50) $contentDepthScore = 10;
        $score += $contentDepthScore;

        // Media completeness (25 points)
        $imageCount = $images['total_count'] ?? 0;
        $imagesWithAlt = $images['with_alt_count'] ?? 0;
        $mediaScore = 0;

        if ($imageCount > 0) {
            $altRatio = $imagesWithAlt / $imageCount;
            $mediaScore += $altRatio * 15; // Alt text coverage

            if ($imageCount >= 1 && $imageCount <= 10) $mediaScore += 10; // Appropriate image count
            elseif ($imageCount > 0) $mediaScore += 5;
        } else {
            $mediaScore += 10; // No images is okay for text-heavy content
        }
        $score += $mediaScore;

        // Information architecture (15 points)
        $infoArchScore = $this->assessInformationArchitecture($content, $meta);
        $score += $infoArchScore;

        return [
            'score' => round($score, 1),
            'meta_completeness' => [
                'score' => round($metaScore, 1),
                'has_title' => !empty($meta['title']),
                'has_description' => !empty($meta['description']),
                'has_keywords' => !empty($meta['keywords']),
                'has_author' => !empty($meta['author']),
                'has_canonical' => !empty($meta['canonical'])
            ],
            'content_depth' => [
                'score' => round($contentDepthScore, 1),
                'word_count' => $wordCount,
                'depth_level' => $this->getContentDepthLevel($wordCount)
            ],
            'media_completeness' => [
                'score' => round($mediaScore, 1),
                'image_count' => $imageCount,
                'alt_text_coverage' => $imageCount > 0 ? round(($imagesWithAlt / $imageCount) * 100, 1) : 0
            ],
            'information_architecture' => [
                'score' => round($infoArchScore, 1),
                'completeness_level' => $this->getCompletenessLevel($score)
            ],
            'completeness_gaps' => $this->identifyCompletenessGaps($meta, $content, $images)
        ];
    }

    /**
     * Assess content engagement potential
     */
    private function assessContentEngagement(array $content, array $links, string $html): array
    {
        // Analyze engagement elements
        $engagementElements = $this->analyzeEngagementElements($html);

        // Analyze link quality and diversity
        $linkAnalysis = $this->analyzeLinkEngagement($links, $html);

        // Assess content interactivity
        $interactivityScore = $this->assessContentInteractivity($html);

        // Analyze content freshness indicators
        $freshnessScore = $this->assessContentFreshness($content, $html);

        // Calculate engagement score (0-100)
        $score = 0;

        // Engagement elements (30 points)
        $score += $engagementElements['score'] * 0.3;

        // Link engagement (25 points)
        $score += $linkAnalysis['score'] * 0.25;

        // Interactivity (25 points)
        $score += $interactivityScore * 0.25;

        // Content freshness (20 points)
        $score += $freshnessScore * 0.2;

        return [
            'score' => round($score, 1),
            'engagement_elements' => $engagementElements,
            'link_analysis' => $linkAnalysis,
            'interactivity' => [
                'score' => round($interactivityScore, 1),
                'interactive_elements' => $this->countInteractiveElements($html)
            ],
            'content_freshness' => [
                'score' => round($freshnessScore, 1),
                'freshness_indicators' => $this->getFreshnessIndicators($content, $html)
            ],
            'engagement_opportunities' => $this->identifyEngagementOpportunities($engagementElements, $linkAnalysis, $interactivityScore)
        ];
    }

    /**
     * Assess content originality
     */
    private function assessContentOriginality(array $content, string $html): array
    {
        // This is a simplified implementation - real originality detection would require
        // more sophisticated algorithms and external APIs

        $text = $this->extractPlainText($html);
        $wordCount = $content['word_count'] ?? $this->countWords($text);

        // Analyze content uniqueness indicators
        $uniquenessScore = $this->analyzeContentUniqueness($text, $html);

        // Check for duplicate content patterns
        $duplicatePatterns = $this->detectDuplicatePatterns($text);

        // Assess content depth and specificity
        $specificityScore = $this->assessContentSpecificity($text, $html);

        // Calculate originality score (0-100)
        $score = 0;

        // Uniqueness indicators (40 points)
        $score += $uniquenessScore * 0.4;

        // Content specificity (35 points)
        $score += $specificityScore * 0.35;

        // Penalty for duplicate patterns (up to -25 points)
        $duplicatePenalty = count($duplicatePatterns) * 5;
        $score -= min(25, $duplicatePenalty);

        // Length bonus for substantial original content
        if ($wordCount >= 500) $score += 15;
        elseif ($wordCount >= 300) $score += 10;

        $score = max(0, $score); // Ensure non-negative

        return [
            'score' => round($score, 1),
            'uniqueness_indicators' => [
                'score' => round($uniquenessScore, 1),
                'unique_phrases' => $this->countUniquePhrases($text),
                'technical_terms' => $this->countTechnicalTerms($text),
                'personal_pronouns' => $this->countPersonalPronouns($text)
            ],
            'specificity' => [
                'score' => round($specificityScore, 1),
                'specific_examples' => $this->countSpecificExamples($text),
                'numbers_and_data' => $this->countNumbersAndData($text)
            ],
            'duplicate_content' => [
                'patterns_found' => count($duplicatePatterns),
                'severity' => $this->getDuplicateSeverity(count($duplicatePatterns)),
                'patterns' => array_slice($duplicatePatterns, 0, 3) // Top 3 patterns
            ],
            'originality_level' => $this->getOriginalityLevel($score)
        ];
    }

    /**
     * Assess content relevance
     */
    private function assessContentRelevance(array $meta, array $content, array $headings): array
    {
        $title = $meta['title'] ?? '';
        $description = $meta['description'] ?? '';

        // Analyze keyword consistency
        $keywordConsistency = $this->analyzeKeywordConsistency($title, $description, $headings, $content);

        // Assess topic focus
        $topicFocus = $this->assessTopicFocus($title, $headings, $content);

        // Analyze semantic coherence
        $semanticCoherence = $this->analyzeSemanticCoherence($content, $headings);

        // Calculate relevance score (0-100)
        $score = 0;

        // Keyword consistency (40 points)
        $score += $keywordConsistency['score'] * 0.4;

        // Topic focus (35 points)
        $score += $topicFocus * 0.35;

        // Semantic coherence (25 points)
        $score += $semanticCoherence * 0.25;

        return [
            'score' => round($score, 1),
            'keyword_consistency' => $keywordConsistency,
            'topic_focus' => [
                'score' => round($topicFocus, 1),
                'primary_topic_strength' => $this->getPrimaryTopicStrength($title, $headings),
                'topic_drift_detected' => $this->detectTopicDrift($headings, $content)
            ],
            'semantic_coherence' => [
                'score' => round($semanticCoherence, 1),
                'coherence_level' => $this->getCoherenceLevel($semanticCoherence)
            ],
            'relevance_issues' => $this->identifyRelevanceIssues($keywordConsistency, $topicFocus, $semanticCoherence)
        ];
    }

    /**
     * Assess technical content quality
     */
    private function assessTechnicalQuality(string $html, array $parsedData): array
    {
        // Analyze HTML quality
        $htmlQuality = $this->assessHtmlQuality($html);

        // Check for technical SEO elements
        $seoTechnicalScore = $this->assessSeoTechnicalElements($parsedData);

        // Analyze markup validation
        $markupValidation = $this->assessMarkupValidation($html);

        // Calculate technical quality score (0-100)
        $score = 0;

        // HTML quality (40 points)
        $score += $htmlQuality * 0.4;

        // SEO technical elements (35 points)
        $score += $seoTechnicalScore * 0.35;

        // Markup validation (25 points)
        $score += $markupValidation * 0.25;

        return [
            'score' => round($score, 1),
            'html_quality' => [
                'score' => round($htmlQuality, 1),
                'semantic_html' => $this->checkSemanticHtml($html),
                'accessibility_features' => $this->checkAccessibilityFeatures($html)
            ],
            'seo_technical' => [
                'score' => round($seoTechnicalScore, 1),
                'meta_tags_quality' => $this->assessMetaTagsQuality($parsedData),
                'structured_data' => $this->checkStructuredData($html)
            ],
            'markup_validation' => [
                'score' => round($markupValidation, 1),
                'validation_issues' => $this->findValidationIssues($html)
            ],
            'technical_recommendations' => $this->generateTechnicalRecommendations($htmlQuality, $seoTechnicalScore, $markupValidation)
        ];
    }

    /**
     * Assess user experience aspects of content
     */
    private function assessUserExperience(array $content, array $structure, string $html): array
    {
        // Analyze content scannability
        $scannability = $this->assessContentScannability($html, $structure);

        // Assess visual content organization
        $visualOrganization = $this->assessVisualOrganization($html);

        // Check for user-friendly elements
        $userFriendlyElements = $this->assessUserFriendlyElements($html);

        // Calculate UX score (0-100)
        $score = 0;

        // Content scannability (40 points)
        $score += $scannability * 0.4;

        // Visual organization (35 points)
        $score += $visualOrganization * 0.35;

        // User-friendly elements (25 points)
        $score += $userFriendlyElements * 0.25;

        return [
            'score' => round($score, 1),
            'scannability' => [
                'score' => round($scannability, 1),
                'bullet_points' => $this->countBulletPoints($html),
                'short_paragraphs' => $this->countShortParagraphs($html),
                'white_space_usage' => $this->assessWhiteSpaceUsage($html)
            ],
            'visual_organization' => [
                'score' => round($visualOrganization, 1),
                'visual_hierarchy' => $this->assessVisualHierarchy($html),
                'content_breaking' => $this->assessContentBreaking($html)
            ],
            'user_friendly_elements' => [
                'score' => round($userFriendlyElements, 1),
                'call_to_actions' => $this->countCallToActions($html),
                'navigation_aids' => $this->countNavigationAids($html)
            ],
            'ux_recommendations' => $this->generateUxRecommendations($scannability, $visualOrganization, $userFriendlyElements)
        ];
    }

    /**
     * Calculate overall content quality score from all dimensions
     */
    private function calculateOverallContentScore(array $dimensions): float
    {
        // Define weights for different quality dimensions
        $weights = [
            'readability' => 0.20,       // 20%
            'structure' => 0.15,         // 15%
            'completeness' => 0.15,      // 15%
            'engagement' => 0.15,        // 15%
            'originality' => 0.10,       // 10%
            'relevance' => 0.10,         // 10%
            'technical_quality' => 0.10, // 10%
            'user_experience' => 0.05    // 5%
        ];

        $totalScore = 0;
        foreach ($dimensions as $dimension => $data) {
            $score = $data['score'] ?? 0;
            $weight = $weights[$dimension] ?? 0;
            $totalScore += $score * $weight;
        }

        return round($totalScore, 1);
    }

    /**
     * Generate quality recommendations based on assessment results
     */
    private function generateQualityRecommendations(array $dimensions): array
    {
        $recommendations = [];

        // Readability recommendations
        $readability = $dimensions['readability'];
        if ($readability['score'] < 60) {
            if ($readability['avg_words_per_sentence'] > 20) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'readability',
                    'message' => 'Sentences are too long',
                    'impact' => 'medium',
                    'fix' => 'Break down complex sentences into shorter, clearer ones (aim for 15-20 words per sentence)'
                ];
            }
            if ($readability['flesch_reading_ease'] < 50) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'readability',
                    'message' => 'Content is difficult to read',
                    'impact' => 'high',
                    'fix' => 'Simplify language, use shorter sentences, and explain technical terms'
                ];
            }
        }

        // Structure recommendations
        $structure = $dimensions['structure'];
        if ($structure['score'] < 70) {
            if ($structure['heading_analysis']['score'] < 60) {
                $recommendations[] = [
                    'type' => 'error',
                    'category' => 'structure',
                    'message' => 'Poor heading structure',
                    'impact' => 'high',
                    'fix' => 'Use proper heading hierarchy (H1, H2, H3) to organize content logically'
                ];
            }
            if ($structure['structural_elements']['total'] < 2) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'structure',
                    'message' => 'Limited use of structural elements',
                    'impact' => 'medium',
                    'fix' => 'Add lists, tables, or blockquotes to improve content organization'
                ];
            }
        }

        // Completeness recommendations
        $completeness = $dimensions['completeness'];
        if ($completeness['score'] < 70) {
            if (!$completeness['meta_completeness']['has_description']) {
                $recommendations[] = [
                    'type' => 'error',
                    'category' => 'completeness',
                    'message' => 'Missing meta description',
                    'impact' => 'high',
                    'fix' => 'Add a compelling meta description (120-160 characters)'
                ];
            }
            if ($completeness['content_depth']['word_count'] < 300) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'completeness',
                    'message' => 'Content is too short',
                    'impact' => 'medium',
                    'fix' => 'Expand content with more detailed information and examples (aim for 300+ words)'
                ];
            }
        }

        // Engagement recommendations
        $engagement = $dimensions['engagement'];
        if ($engagement['score'] < 60) {
            if ($engagement['link_analysis']['score'] < 50) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'engagement',
                    'message' => 'Limited internal linking',
                    'impact' => 'medium',
                    'fix' => 'Add relevant internal links to related content on your site'
                ];
            }
            if ($engagement['interactivity']['score'] < 40) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'engagement',
                    'message' => 'Low content interactivity',
                    'impact' => 'low',
                    'fix' => 'Consider adding interactive elements like forms, buttons, or multimedia'
                ];
            }
        }

        // Originality recommendations
        $originality = $dimensions['originality'];
        if ($originality['score'] < 60) {
            if ($originality['duplicate_content']['patterns_found'] > 0) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'originality',
                    'message' => 'Potential duplicate content detected',
                    'impact' => 'high',
                    'fix' => 'Review and rewrite sections that may be duplicated from other sources'
                ];
            }
            if ($originality['specificity']['score'] < 50) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'originality',
                    'message' => 'Content lacks specificity',
                    'impact' => 'medium',
                    'fix' => 'Add specific examples, data, and unique insights to make content more valuable'
                ];
            }
        }

        return $recommendations;
    }

    // Helper methods for content analysis

    private function extractPlainText(string $html): string
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

    private function countWords(string $text): int
    {
        return str_word_count($text);
    }

    private function countSentences(string $text): int
    {
        return preg_match_all('/[.!?]+/', $text);
    }

    private function countParagraphs(string $html): int
    {
        return preg_match_all('/<p[^>]*>/', $html);
    }

    private function calculateFleschScore(string $text, int $wordCount, int $sentenceCount): float
    {
        if ($wordCount === 0 || $sentenceCount === 0) {
            return 0;
        }

        // Count syllables (simplified approximation)
        $syllableCount = $this->countSyllables($text);

        // Flesch Reading Ease formula
        $avgSentenceLength = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);

        return max(0, min(100, $score));
    }

    private function countSyllables(string $text): int
    {
        // Simplified syllable counting - count vowel groups
        $text = strtolower($text);
        $syllableCount = preg_match_all('/[aeiouy]+/', $text);

        return max(1, $syllableCount); // At least 1 syllable per word group
    }

    private function getReadingLevel(float $fleschScore): string
    {
        if ($fleschScore >= 90) return 'Very Easy';
        if ($fleschScore >= 80) return 'Easy';
        if ($fleschScore >= 70) return 'Fairly Easy';
        if ($fleschScore >= 60) return 'Standard';
        if ($fleschScore >= 50) return 'Fairly Difficult';
        if ($fleschScore >= 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function analyzeComplexity(string $text): array
    {
        // Count complex words (3+ syllables)
        $words = explode(' ', $text);
        $complexWords = 0;

        foreach ($words as $word) {
            if ($this->countSyllables($word) >= 3) {
                $complexWords++;
            }
        }

        return [
            'complex_words' => $complexWords,
            'complex_word_ratio' => count($words) > 0 ? round(($complexWords / count($words)) * 100, 1) : 0,
            'passive_voice_usage' => $this->detectPassiveVoice($text)
        ];
    }

    private function detectPassiveVoice(string $text): int
    {
        // Simple passive voice detection
        return preg_match_all('/\b(was|were|is|are|been)\s+\w+ed\b/i', $text);
    }

    private function identifyReadabilityIssues(float $avgWordsPerSentence, float $avgWordsPerParagraph, float $fleschScore): array
    {
        $issues = [];

        if ($avgWordsPerSentence > 25) {
            $issues[] = 'Sentences are too long (>25 words average)';
        }
        if ($avgWordsPerParagraph > 200) {
            $issues[] = 'Paragraphs are too long (>200 words average)';
        }
        if ($fleschScore < 30) {
            $issues[] = 'Content is very difficult to read';
        }

        return $issues;
    }

    // Additional helper methods would be implemented here for:
    // - analyzeHeadingHierarchy()
    // - countLists(), countTables(), countBlockquotes()
    // - analyzeContentOrganization()
    // - assessInformationArchitecture()
    // - analyzeEngagementElements()
    // - analyzeLinkEngagement()
    // - assessContentInteractivity()
    // - assessContentFreshness()
    // - analyzeContentUniqueness()
    // - detectDuplicatePatterns()
    // - assessContentSpecificity()
    // - analyzeKeywordConsistency()
    // - assessTopicFocus()
    // - analyzeSemanticCoherence()
    // - assessHtmlQuality()
    // - assessSeoTechnicalElements()
    // - assessMarkupValidation()
    // - assessContentScannability()
    // - assessVisualOrganization()
    // - assessUserFriendlyElements()

    // For brevity, I'll include key helper methods

    private function analyzeHeadingHierarchy(array $headings): array
    {
        $score = 0;
        $issues = [];

        // Check for H1
        $h1Count = count($headings['h1'] ?? []);
        if ($h1Count === 1) {
            $score += 30;
        } elseif ($h1Count === 0) {
            $issues[] = 'Missing H1 heading';
        } elseif ($h1Count > 1) {
            $issues[] = 'Multiple H1 headings found';
            $score += 15;
        }

        // Check for logical hierarchy
        $hasH2 = count($headings['h2'] ?? []) > 0;
        $hasH3 = count($headings['h3'] ?? []) > 0;

        if ($hasH2) $score += 20;
        if ($hasH3) $score += 15;

        // Check for proper nesting (simplified)
        if ($hasH3 && !$hasH2) {
            $issues[] = 'H3 used without H2 (poor hierarchy)';
            $score -= 10;
        }

        // Bonus for good distribution
        $totalHeadings = array_sum(array_map('count', $headings));
        if ($totalHeadings >= 3 && $totalHeadings <= 10) {
            $score += 35;
        } elseif ($totalHeadings > 0) {
            $score += 15;
        }

        return [
            'score' => max(0, min(100, $score)),
            'h1_count' => $h1Count,
            'total_headings' => $totalHeadings,
            'has_hierarchy' => $hasH2 && $hasH3,
            'issues' => $issues
        ];
    }

    private function countLists(string $html): int
    {
        return preg_match_all('/<(ul|ol)[^>]*>/', $html);
    }

    private function countTables(string $html): int
    {
        return preg_match_all('/<table[^>]*>/', $html);
    }

    private function countBlockquotes(string $html): int
    {
        return preg_match_all('/<blockquote[^>]*>/', $html);
    }

    private function analyzeContentOrganization(string $html, array $headings): float
    {
        // Simplified organization assessment
        $score = 50; // Base score

        // Check for proper content sections
        $sections = preg_match_all('/<(section|article|div[^>]*class[^>]*section)[^>]*>/', $html);
        if ($sections > 0) $score += 20;

        // Check for content breaks
        $paragraphs = preg_match_all('/<p[^>]*>/', $html);
        if ($paragraphs > 3) $score += 15;

        // Heading distribution
        $totalHeadings = array_sum(array_map('count', $headings));
        if ($totalHeadings >= 2) $score += 15;

        return min(100, $score);
    }

    private function getContentDepthLevel(int $wordCount): string
    {
        if ($wordCount >= 2000) return 'Very Deep';
        if ($wordCount >= 1000) return 'Deep';
        if ($wordCount >= 500) return 'Moderate';
        if ($wordCount >= 300) return 'Basic';
        if ($wordCount >= 150) return 'Shallow';
        return 'Very Shallow';
    }

    private function getCompletenessLevel(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Very Poor';
    }

    private function identifyCompletenessGaps(array $meta, array $content, array $images): array
    {
        $gaps = [];

        if (empty($meta['title'])) $gaps[] = 'Missing page title';
        if (empty($meta['description'])) $gaps[] = 'Missing meta description';
        if (($content['word_count'] ?? 0) < 300) $gaps[] = 'Insufficient content length';
        if (($images['total_count'] ?? 0) > ($images['with_alt_count'] ?? 0)) {
            $gaps[] = 'Images missing alt text';
        }

        return $gaps;
    }

    private function generateQualityInsights(float $overallScore, array $readability, array $structure, array $engagement): array
    {
        $insights = [];

        // Overall assessment
        if ($overallScore >= 80) {
            $insights[] = 'Content demonstrates high quality across most dimensions';
        } elseif ($overallScore >= 60) {
            $insights[] = 'Content has good foundation but needs improvement in key areas';
        } else {
            $insights[] = 'Content requires significant quality improvements';
        }

        // Specific insights
        if ($readability['score'] < 50) {
            $insights[] = 'Content readability is a major concern that affects user experience';
        }
        if ($structure['score'] < 60) {
            $insights[] = 'Poor content structure makes it difficult for users to consume information';
        }
        if ($engagement['score'] < 50) {
            $insights[] = 'Limited engagement elements may result in high bounce rates';
        }

        return $insights;
    }

    private function identifyImprovementPriorities(float $overallScore, array $dimensions): array
    {
        $priorities = [];

        // Sort dimensions by score (lowest first)
        $scores = [];
        foreach ($dimensions as $dimension => $data) {
            $scores[$dimension] = $data['score'] ?? 0;
        }
        asort($scores);

        $priority_levels = ['high', 'medium', 'low'];
        $i = 0;

        foreach ($scores as $dimension => $score) {
            if ($score < 60 && $i < 3) {
                $priorities[] = [
                    'dimension' => $dimension,
                    'score' => $score,
                    'priority' => $priority_levels[$i] ?? 'low',
                    'impact' => $this->getDimensionImpact($dimension)
                ];
                $i++;
            }
        }

        return $priorities;
    }

    private function getDimensionImpact(string $dimension): string
    {
        $impacts = [
            'readability' => 'high',
            'structure' => 'high',
            'completeness' => 'medium',
            'engagement' => 'medium',
            'originality' => 'medium',
            'relevance' => 'high',
            'technical_quality' => 'low',
            'user_experience' => 'medium'
        ];

        return $impacts[$dimension] ?? 'low';
    }

    private function getTopStrength(array $dimensions): string
    {
        $maxScore = 0;
        $topDimension = '';

        foreach ($dimensions as $dimension => $data) {
            if (($data['score'] ?? 0) > $maxScore) {
                $maxScore = $data['score'] ?? 0;
                $topDimension = $dimension;
            }
        }

        return $topDimension;
    }

    private function getMainWeakness(array $dimensions): string
    {
        $minScore = 100;
        $weakestDimension = '';

        foreach ($dimensions as $dimension => $data) {
            if (($data['score'] ?? 100) < $minScore) {
                $minScore = $data['score'] ?? 100;
                $weakestDimension = $dimension;
            }
        }

        return $weakestDimension;
    }

    // Simplified implementations for remaining methods
    private function assessInformationArchitecture(array $content, array $meta): float
    {
        return 75; // Simplified - would analyze content organization in detail
    }

    private function analyzeEngagementElements(string $html): array
    {
        return ['score' => 70]; // Simplified - would analyze CTAs, multimedia, etc.
    }

    private function analyzeLinkEngagement(array $links, string $html): array
    {
        return ['score' => 60]; // Simplified - would analyze link quality and distribution
    }

    private function assessContentInteractivity(string $html): float
    {
        return 50; // Simplified - would count forms, buttons, etc.
    }

    private function assessContentFreshness(array $content, string $html): float
    {
        return 60; // Simplified - would check dates, recent references, etc.
    }

    private function analyzeContentUniqueness(string $text, string $html): float
    {
        return 75; // Simplified - would require more sophisticated analysis
    }

    private function detectDuplicatePatterns(string $text): array
    {
        return []; // Simplified - would detect repeated phrases/content
    }

    private function assessContentSpecificity(string $text, string $html): float
    {
        return 70; // Simplified - would analyze specificity indicators
    }

    private function analyzeKeywordConsistency(string $title, string $description, array $headings, array $content): array
    {
        return ['score' => 65]; // Simplified - would analyze keyword distribution
    }

    private function assessTopicFocus(string $title, array $headings, array $content): float
    {
        return 70; // Simplified - would analyze topic coherence
    }

    private function analyzeSemanticCoherence(array $content, array $headings): float
    {
        return 68; // Simplified - would analyze semantic relationships
    }

    private function assessHtmlQuality(string $html): float
    {
        return 75; // Simplified - would validate HTML structure
    }

    private function assessSeoTechnicalElements(array $parsedData): float
    {
        return 80; // Simplified - would check meta tags, structured data, etc.
    }

    private function assessMarkupValidation(string $html): float
    {
        return 85; // Simplified - would validate HTML markup
    }

    private function assessContentScannability(string $html, array $structure): float
    {
        return 70; // Simplified - would analyze visual hierarchy
    }

    private function assessVisualOrganization(string $html): float
    {
        return 65; // Simplified - would analyze layout structure
    }

    private function assessUserFriendlyElements(string $html): float
    {
        return 60; // Simplified - would count user-friendly features
    }

    // Additional placeholder methods for completeness
    private function extractContentMetrics(array $content, string $html): array
    {
        return [
            'word_count' => $content['word_count'] ?? 0,
            'character_count' => strlen($this->extractPlainText($html)),
            'paragraph_count' => $this->countParagraphs($html),
            'list_count' => $this->countLists($html)
        ];
    }

    private function countUniquePhrases(string $text): int { return 10; }
    private function countTechnicalTerms(string $text): int { return 5; }
    private function countPersonalPronouns(string $text): int { return 8; }
    private function countSpecificExamples(string $text): int { return 3; }
    private function countNumbersAndData(string $text): int { return 7; }
    private function getDuplicateSeverity(int $count): string { return $count > 3 ? 'High' : 'Low'; }
    private function getOriginalityLevel(float $score): string { return $score > 70 ? 'Good' : 'Poor'; }
    private function getPrimaryTopicStrength(string $title, array $headings): float { return 70; }
    private function detectTopicDrift(array $headings, array $content): bool { return false; }
    private function getCoherenceLevel(float $score): string { return $score > 70 ? 'Good' : 'Poor'; }
    private function identifyRelevanceIssues(array $keywords, float $focus, float $coherence): array { return []; }
    private function identifyStructureIssues(array $heading, float $org, int $elements): array { return []; }
    private function analyzeContentFlow(string $html, array $headings): array { return ['flow_score' => 70]; }
    private function identifyEngagementOpportunities(array $elements, array $links, float $interactivity): array { return []; }
    private function getFreshnessIndicators(array $content, string $html): array { return []; }
    private function countInteractiveElements(string $html): int { return 2; }
    private function checkSemanticHtml(string $html): bool { return true; }
    private function checkAccessibilityFeatures(string $html): bool { return true; }
    private function assessMetaTagsQuality(array $parsedData): float { return 80; }
    private function checkStructuredData(string $html): bool { return false; }
    private function findValidationIssues(string $html): array { return []; }
    private function generateTechnicalRecommendations(float $html, float $seo, float $markup): array { return []; }
    private function countBulletPoints(string $html): int { return 5; }
    private function countShortParagraphs(string $html): int { return 3; }
    private function assessWhiteSpaceUsage(string $html): float { return 70; }
    private function assessVisualHierarchy(string $html): float { return 75; }
    private function assessContentBreaking(string $html): float { return 65; }
    private function countCallToActions(string $html): int { return 2; }
    private function countNavigationAids(string $html): int { return 1; }
    private function generateUxRecommendations(float $scan, float $visual, float $friendly): array { return []; }
}