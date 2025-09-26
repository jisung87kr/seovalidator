<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Link analysis service for internal and external link analysis
 * Analyzes link quality, anchor text optimization, link distribution,
 * and identifies opportunities for improved link strategy
 */
class LinkAnalyzer
{
    /**
     * Analyze internal and external links
     */
    public function analyze(array $linksData, string $html = '', string $url = '', array $options = []): array
    {
        Log::debug('Starting link analysis', [
            'total_links' => $linksData['total_count'] ?? 0,
            'url' => $url
        ]);

        $startTime = microtime(true);

        try {
            $links = $linksData['links'] ?? [];

            if (empty($links)) {
                return $this->generateEmptyLinkAnalysis();
            }

            // Separate internal and external links
            $linkCategories = $this->categorizeLinks($links, $url);

            // Analyze internal link structure
            $internalAnalysis = $this->analyzeInternalLinks($linkCategories['internal'], $html, $url);

            // Analyze external link quality
            $externalAnalysis = $this->analyzeExternalLinks($linkCategories['external'], $options);

            // Analyze anchor text optimization
            $anchorTextAnalysis = $this->analyzeAnchorText($links);

            // Check link accessibility and usability
            $accessibilityAnalysis = $this->analyzeAccessibility($links, $html);

            // Analyze link distribution and patterns
            $distributionAnalysis = $this->analyzeDistribution($links, $html);

            // Check for SEO link issues
            $seoAnalysis = $this->analyzeSeoCompliance($links, $linkCategories);

            // Security and safety analysis
            $securityAnalysis = $this->analyzeSecurityCompliance($linkCategories['external']);

            // Calculate overall link quality score
            $overallScore = $this->calculateLinkScore([
                'internal' => $internalAnalysis,
                'external' => $externalAnalysis,
                'anchor_text' => $anchorTextAnalysis,
                'accessibility' => $accessibilityAnalysis,
                'distribution' => $distributionAnalysis,
                'seo' => $seoAnalysis,
                'security' => $securityAnalysis
            ]);

            // Generate recommendations
            $recommendations = $this->generateLinkRecommendations([
                'internal' => $internalAnalysis,
                'external' => $externalAnalysis,
                'anchor_text' => $anchorTextAnalysis,
                'accessibility' => $accessibilityAnalysis,
                'distribution' => $distributionAnalysis,
                'seo' => $seoAnalysis,
                'security' => $securityAnalysis,
                'overall_score' => $overallScore
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'total_links' => count($links),
                'link_summary' => $this->generateLinkSummary($linkCategories, $linksData),
                'internal_analysis' => $internalAnalysis,
                'external_analysis' => $externalAnalysis,
                'anchor_text_analysis' => $anchorTextAnalysis,
                'accessibility_analysis' => $accessibilityAnalysis,
                'distribution_analysis' => $distributionAnalysis,
                'seo_analysis' => $seoAnalysis,
                'security_analysis' => $securityAnalysis,
                'recommendations' => $recommendations,
                'link_insights' => $this->generateLinkInsights($overallScore, $linkCategories, $anchorTextAnalysis)
            ];

            Log::info('Link analysis completed', [
                'total_links' => count($links),
                'internal_links' => count($linkCategories['internal']),
                'external_links' => count($linkCategories['external']),
                'overall_score' => $overallScore,
                'recommendations_count' => count($recommendations)
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Link analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Categorize links into internal, external, and special types
     */
    private function categorizeLinks(array $links, string $baseUrl): array
    {
        $internal = [];
        $external = [];
        $anchor = [];
        $mailto = [];
        $tel = [];
        $other = [];

        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        foreach ($links as $link) {
            $href = $link['href'] ?? '';

            if (str_starts_with($href, '#')) {
                $anchor[] = $link;
            } elseif (str_starts_with($href, 'mailto:')) {
                $mailto[] = $link;
            } elseif (str_starts_with($href, 'tel:')) {
                $tel[] = $link;
            } elseif ($link['is_external'] ?? false) {
                $external[] = $link;
            } elseif (!empty($href)) {
                $internal[] = $link;
            } else {
                $other[] = $link;
            }
        }

        return [
            'internal' => $internal,
            'external' => $external,
            'anchor' => $anchor,
            'mailto' => $mailto,
            'tel' => $tel,
            'other' => $other
        ];
    }

    /**
     * Analyze internal link structure and quality
     */
    private function analyzeInternalLinks(array $internalLinks, string $html, string $baseUrl): array
    {
        $score = 100;
        $issues = [];

        if (empty($internalLinks)) {
            return [
                'score' => 20,
                'quality_level' => 'Poor',
                'issues' => ['No internal links found - poor for SEO and user navigation'],
                'link_analysis' => [],
                'navigation_analysis' => [],
                'depth_analysis' => []
            ];
        }

        $linkAnalysis = [];
        $urlCounts = [];
        $anchorTextAnalysis = [];

        foreach ($internalLinks as $link) {
            $href = $link['href'] ?? '';
            $anchorText = $link['anchor_text'] ?? '';

            // Count URL frequency
            $urlCounts[$href] = ($urlCounts[$href] ?? 0) + 1;

            $analysis = [
                'href' => $href,
                'anchor_text' => $anchorText,
                'anchor_text_length' => strlen($anchorText),
                'is_descriptive' => $this->isDescriptiveAnchorText($anchorText),
                'has_title' => $link['has_title'] ?? false,
                'is_empty_anchor' => empty($anchorText),
                'url_depth' => $this->calculateUrlDepth($href),
                'appears_to_be_navigation' => $this->appearsToBeNavigation($anchorText, $href)
            ];

            if ($analysis['is_empty_anchor']) {
                $score -= 10;
                $issues[] = "Empty anchor text for link: {$href}";
            } elseif (!$analysis['is_descriptive']) {
                $score -= 5;
                $issues[] = "Non-descriptive anchor text: '{$anchorText}'";
            }

            if ($analysis['url_depth'] > 3) {
                $score -= 3;
            }

            $linkAnalysis[] = $analysis;

            // Collect anchor text for analysis
            if (!empty($anchorText)) {
                $anchorTextAnalysis[] = $anchorText;
            }
        }

        // Check for over-linking to same URLs
        foreach ($urlCounts as $url => $count) {
            if ($count > 3) {
                $issues[] = "URL '{$url}' linked {$count} times - may be excessive";
                $score -= 5;
            }
        }

        // Analyze navigation patterns
        $navigationAnalysis = $this->analyzeNavigationPatterns($linkAnalysis, $html);

        // Analyze link depth distribution
        $depthAnalysis = $this->analyzeDepthDistribution($linkAnalysis);

        return [
            'score' => max(0, min(100, round($score, 1))),
            'quality_level' => $this->getLinkQualityLevel($score),
            'issues' => $issues,
            'total_internal_links' => count($internalLinks),
            'unique_urls' => count($urlCounts),
            'link_analysis' => $linkAnalysis,
            'navigation_analysis' => $navigationAnalysis,
            'depth_analysis' => $depthAnalysis,
            'url_frequency' => $urlCounts,
            'anchor_text_diversity' => $this->calculateAnchorTextDiversity($anchorTextAnalysis)
        ];
    }

    /**
     * Analyze external link quality and safety
     */
    private function analyzeExternalLinks(array $externalLinks, array $options): array
    {
        $score = 100;
        $issues = [];

        if (empty($externalLinks)) {
            return [
                'score' => 50, // Neutral score for no external links
                'quality_level' => 'Neutral',
                'issues' => [],
                'link_analysis' => [],
                'domain_analysis' => [],
                'safety_analysis' => []
            ];
        }

        $linkAnalysis = [];
        $domainCounts = [];
        $nofollowCount = 0;
        $safetyIssues = [];

        foreach ($externalLinks as $link) {
            $href = $link['href'] ?? '';
            $anchorText = $link['anchor_text'] ?? '';
            $rel = $link['rel'] ?? '';

            $domain = parse_url($href, PHP_URL_HOST);
            if ($domain) {
                $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
            }

            $analysis = [
                'href' => $href,
                'domain' => $domain,
                'anchor_text' => $anchorText,
                'is_nofollow' => $link['is_nofollow'] ?? false,
                'has_noopener' => str_contains(strtolower($rel), 'noopener'),
                'has_noreferrer' => str_contains(strtolower($rel), 'noreferrer'),
                'target_blank' => str_contains($href, 'target="_blank"'),
                'authority_domain' => $this->isAuthorityDomain($domain),
                'potential_spam' => $this->isPotentialSpamDomain($domain),
                'safety_score' => $this->calculateDomainSafetyScore($domain, $href)
            ];

            if ($analysis['is_nofollow']) {
                $nofollowCount++;
            }

            if ($analysis['target_blank'] && !$analysis['has_noopener']) {
                $score -= 8;
                $issues[] = "External link without noopener: {$href}";
            }

            if ($analysis['potential_spam']) {
                $score -= 15;
                $safetyIssues[] = "Potential spam domain: {$domain}";
            }

            if (!$analysis['authority_domain'] && !$analysis['is_nofollow']) {
                $score -= 3; // Minor penalty for following low-authority domains
            }

            $linkAnalysis[] = $analysis;
        }

        // Check for excessive external linking
        if (count($externalLinks) > 10) {
            $score -= 10;
            $issues[] = "High number of external links ({count}) may dilute page authority";
        }

        // Analyze domain diversity
        $domainAnalysis = $this->analyzeDomainDiversity($domainCounts);

        return [
            'score' => max(0, min(100, round($score, 1))),
            'quality_level' => $this->getLinkQualityLevel($score),
            'issues' => $issues,
            'safety_issues' => $safetyIssues,
            'total_external_links' => count($externalLinks),
            'nofollow_count' => $nofollowCount,
            'nofollow_percentage' => count($externalLinks) > 0 ? round(($nofollowCount / count($externalLinks)) * 100, 1) : 0,
            'link_analysis' => $linkAnalysis,
            'domain_analysis' => $domainAnalysis,
            'safety_analysis' => $this->analyzeDomainSafety($linkAnalysis)
        ];
    }

    /**
     * Analyze anchor text optimization across all links
     */
    private function analyzeAnchorText(array $links): array
    {
        $score = 100;
        $issues = [];

        $anchorTexts = array_filter(array_column($links, 'anchor_text'), fn($text) => !empty($text));

        if (empty($anchorTexts)) {
            return [
                'score' => 0,
                'quality_level' => 'Very Poor',
                'issues' => ['No anchor text found in any links'],
                'anchor_analysis' => [],
                'diversity_analysis' => [],
                'keyword_analysis' => []
            ];
        }

        $anchorAnalysis = [];
        $keywordFrequency = [];
        $lengthDistribution = [];

        foreach ($anchorTexts as $text) {
            $analysis = [
                'text' => $text,
                'length' => strlen($text),
                'word_count' => str_word_count($text),
                'is_descriptive' => $this->isDescriptiveAnchorText($text),
                'is_generic' => $this->isGenericAnchorText($text),
                'contains_keywords' => $this->containsLikelyKeywords($text),
                'quality_score' => $this->calculateAnchorTextQuality($text)
            ];

            if ($analysis['is_generic']) {
                $score -= 5;
                $issues[] = "Generic anchor text: '{$text}'";
            }

            if ($analysis['length'] < 3) {
                $score -= 8;
                $issues[] = "Anchor text too short: '{$text}'";
            } elseif ($analysis['length'] > 60) {
                $score -= 3;
                $issues[] = "Anchor text too long: '{$text}'";
            }

            $lengthDistribution[] = $analysis['length'];
            $anchorAnalysis[] = $analysis;

            // Extract potential keywords
            $words = array_filter(explode(' ', strtolower($text)), fn($word) => strlen($word) > 3);
            foreach ($words as $word) {
                $keywordFrequency[$word] = ($keywordFrequency[$word] ?? 0) + 1;
            }
        }

        // Check for over-optimization
        foreach ($keywordFrequency as $keyword => $frequency) {
            if ($frequency > 3 && count($anchorTexts) > 5) {
                $score -= 10;
                $issues[] = "Potential keyword stuffing in anchor text: '{$keyword}' used {$frequency} times";
            }
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'quality_level' => $this->getLinkQualityLevel($score),
            'issues' => $issues,
            'total_anchor_texts' => count($anchorTexts),
            'average_length' => round(array_sum($lengthDistribution) / count($lengthDistribution), 1),
            'anchor_analysis' => $anchorAnalysis,
            'diversity_analysis' => $this->calculateTextDiversity($anchorTexts),
            'keyword_analysis' => [
                'keyword_frequency' => $keywordFrequency,
                'most_used_keywords' => $this->getTopKeywords($keywordFrequency, 10),
                'keyword_stuffing_risk' => $this->assessKeywordStuffingRisk($keywordFrequency, count($anchorTexts))
            ]
        ];
    }

    /**
     * Analyze link accessibility compliance
     */
    private function analyzeAccessibility(array $links, string $html): array
    {
        $score = 100;
        $issues = [];
        $accessibilityChecks = [];

        foreach ($links as $link) {
            $href = $link['href'] ?? '';
            $anchorText = $link['anchor_text'] ?? '';
            $title = $link['title'] ?? '';

            $checks = [
                'has_anchor_text' => !empty($anchorText),
                'anchor_text_descriptive' => $this->isDescriptiveAnchorText($anchorText),
                'not_click_here' => !$this->isGenericAnchorText($anchorText),
                'external_indication' => $this->hasExternalIndication($link, $html),
                'keyboard_accessible' => true, // Assume true unless we can check tabindex
                'proper_contrast' => true // Would need color analysis
            ];

            $accessibilityScore = (array_sum($checks) / count($checks)) * 100;

            $accessibilityChecks[] = [
                'href' => $href,
                'anchor_text' => $anchorText,
                'accessibility_score' => round($accessibilityScore, 1),
                'checks' => $checks,
                'issues' => $this->identifyAccessibilityIssues($checks, $anchorText, $href)
            ];

            if ($accessibilityScore < 60) {
                $score -= 10;
            } elseif ($accessibilityScore < 80) {
                $score -= 5;
            }
        }

        // Check for link density (accessibility concern)
        $linkDensity = $this->calculateLinkDensity($links, $html);
        if ($linkDensity > 0.3) { // More than 30% links
            $score -= 15;
            $issues[] = 'High link density may impact accessibility and user experience';
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'compliance_level' => $this->getAccessibilityComplianceLevel($score),
            'issues' => $issues,
            'accessibility_checks' => $accessibilityChecks,
            'link_density' => round($linkDensity, 3),
            'wcag_compliance' => $this->determineWCAGLinkCompliance($score, $accessibilityChecks)
        ];
    }

    /**
     * Analyze link distribution patterns
     */
    private function analyzeDistribution(array $links, string $html): array
    {
        $score = 100;

        if (empty($links)) {
            return [
                'score' => 0,
                'distribution_quality' => 'Poor',
                'patterns' => [],
                'recommendations' => ['Add internal and external links to improve content value']
            ];
        }

        // Analyze distribution by type
        $typeDistribution = $this->getTypeDistribution($links);

        // Analyze placement patterns
        $placementAnalysis = $this->analyzePlacementPatterns($links, $html);

        // Check balance
        $balanceAnalysis = $this->analyzeBalance($typeDistribution);

        if ($balanceAnalysis['internal_external_ratio'] < 0.5) {
            $score -= 15; // Too few internal links
        } elseif ($balanceAnalysis['internal_external_ratio'] > 5) {
            $score -= 10; // Too few external links
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'distribution_quality' => $this->getDistributionQuality($score),
            'type_distribution' => $typeDistribution,
            'placement_analysis' => $placementAnalysis,
            'balance_analysis' => $balanceAnalysis,
            'recommendations' => $this->generateDistributionRecommendations($typeDistribution, $balanceAnalysis)
        ];
    }

    /**
     * Analyze SEO compliance for links
     */
    private function analyzeSeoCompliance(array $links, array $linkCategories): array
    {
        $score = 100;
        $issues = [];

        // Check internal linking for SEO
        $internalCount = count($linkCategories['internal']);
        if ($internalCount < 3) {
            $score -= 20;
            $issues[] = 'Insufficient internal linking for SEO';
        } elseif ($internalCount > 50) {
            $score -= 10;
            $issues[] = 'Excessive internal linking may dilute link equity';
        }

        // Check external link strategy
        $externalCount = count($linkCategories['external']);
        $nofollowCount = count(array_filter($linkCategories['external'], fn($link) => $link['is_nofollow'] ?? false));

        if ($externalCount > 0) {
            $nofollowRatio = $nofollowCount / $externalCount;
            if ($nofollowRatio < 0.5) {
                $score -= 10;
                $issues[] = 'Consider adding nofollow to more external links to preserve link equity';
            }
        }

        // Check for orphan pages risk
        if ($internalCount === 0) {
            $score -= 30;
            $issues[] = 'No internal links - page may become orphaned';
        }

        // Check for link schemes or manipulation
        $manipulationRisk = $this->assessManipulationRisk($links);
        if ($manipulationRisk['risk_level'] === 'high') {
            $score -= 25;
            $issues = array_merge($issues, $manipulationRisk['issues']);
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'seo_optimized' => $score >= 70,
            'issues' => $issues,
            'internal_linking_strength' => $this->calculateInternalLinkingStrength($internalCount),
            'external_linking_strategy' => $this->evaluateExternalLinkingStrategy($externalCount, $nofollowCount),
            'manipulation_risk' => $manipulationRisk,
            'link_equity_distribution' => $this->analyzeLinkEquityDistribution($linkCategories)
        ];
    }

    /**
     * Analyze security compliance for external links
     */
    private function analyzeSecurityCompliance(array $externalLinks): array
    {
        $score = 100;
        $securityIssues = [];

        if (empty($externalLinks)) {
            return [
                'score' => 100,
                'security_level' => 'Secure',
                'issues' => [],
                'recommendations' => []
            ];
        }

        foreach ($externalLinks as $link) {
            $href = $link['href'] ?? '';
            $rel = $link['rel'] ?? '';

            // Check for security attributes
            $hasNoopener = str_contains(strtolower($rel), 'noopener');
            $hasNoreferrer = str_contains(strtolower($rel), 'noreferrer');
            $isTargetBlank = str_contains($href, 'target="_blank"');

            if ($isTargetBlank && !$hasNoopener) {
                $score -= 15;
                $securityIssues[] = "Missing rel='noopener' for target='_blank' link: {$href}";
            }

            // Check for suspicious URLs
            $suspiciousPatterns = [
                '/bit\.ly|tinyurl|t\.co|short\.link/i',
                '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', // IP addresses
                '/[a-z0-9]{20,}\.com/' // Suspicious random domains
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $href)) {
                    $score -= 10;
                    $securityIssues[] = "Potentially suspicious URL pattern: {$href}";
                    break;
                }
            }
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'security_level' => $this->getSecurityLevel($score),
            'issues' => $securityIssues,
            'recommendations' => $this->generateSecurityRecommendations($securityIssues)
        ];
    }

    /**
     * Calculate overall link quality score
     */
    private function calculateLinkScore(array $analyses): array
    {
        $weights = [
            'internal' => 0.25,       // 25%
            'external' => 0.20,       // 20%
            'anchor_text' => 0.20,    // 20%
            'accessibility' => 0.15,  // 15%
            'seo' => 0.10,           // 10%
            'distribution' => 0.05,   // 5%
            'security' => 0.05       // 5%
        ];

        $totalScore = 0;
        $componentScores = [];

        foreach ($analyses as $type => $analysis) {
            $score = $analysis['score'] ?? 0;
            $weight = $weights[$type] ?? 0;
            $componentScores[$type] = $score;
            $totalScore += $score * $weight;
        }

        return [
            'overall' => round($totalScore, 1),
            'components' => $componentScores,
            'weights' => $weights,
            'grade' => $this->getLinkGrade($totalScore)
        ];
    }

    // Helper methods

    private function generateEmptyLinkAnalysis(): array
    {
        return [
            'analyzed_at' => date('c'),
            'analysis_duration_ms' => 0,
            'overall_score' => ['overall' => 20, 'grade' => 'D'],
            'total_links' => 0,
            'link_summary' => ['no_links' => true],
            'recommendations' => [[
                'type' => 'warning',
                'category' => 'link_building',
                'message' => 'No links found on the page',
                'impact' => 'high',
                'fix' => 'Add internal links for navigation and external links for references and authority'
            ]],
            'link_insights' => ['No links found - missing important SEO and user experience elements']
        ];
    }

    private function isDescriptiveAnchorText(string $text): bool
    {
        if (empty($text) || strlen($text) < 3) return false;

        $genericTerms = [
            'click here', 'read more', 'more info', 'learn more', 'here',
            'this', 'link', 'website', 'page', 'article', 'post'
        ];

        $lowercaseText = strtolower(trim($text));

        foreach ($genericTerms as $term) {
            if ($lowercaseText === $term) {
                return false;
            }
        }

        return str_word_count($text) >= 2;
    }

    private function calculateUrlDepth(string $href): int
    {
        $path = parse_url($href, PHP_URL_PATH);
        if (!$path || $path === '/') return 0;

        return count(array_filter(explode('/', trim($path, '/'))));
    }

    private function appearsToBeNavigation(string $anchorText, string $href): bool
    {
        $navigationTerms = ['home', 'about', 'contact', 'services', 'products', 'blog', 'news'];
        $text = strtolower($anchorText);

        foreach ($navigationTerms as $term) {
            if (str_contains($text, $term)) {
                return true;
            }
        }

        return false;
    }

    private function analyzeNavigationPatterns(array $linkAnalysis, string $html): array
    {
        $navigationLinks = array_filter($linkAnalysis, fn($link) => $link['appears_to_be_navigation']);

        return [
            'navigation_links_count' => count($navigationLinks),
            'has_breadcrumbs' => $this->hasBreadcrumbs($html),
            'has_footer_links' => $this->hasFooterLinks($html),
            'navigation_quality' => $this->assessNavigationQuality($navigationLinks)
        ];
    }

    private function analyzeDepthDistribution(array $linkAnalysis): array
    {
        $depths = array_column($linkAnalysis, 'url_depth');
        $distribution = array_count_values($depths);

        return [
            'depth_distribution' => $distribution,
            'average_depth' => !empty($depths) ? round(array_sum($depths) / count($depths), 2) : 0,
            'max_depth' => !empty($depths) ? max($depths) : 0,
            'shallow_links_percentage' => count($depths) > 0 ? round((count(array_filter($depths, fn($d) => $d <= 1)) / count($depths)) * 100, 1) : 0
        ];
    }

    private function calculateAnchorTextDiversity(array $anchorTexts): array
    {
        $uniqueTexts = array_unique($anchorTexts);
        $totalTexts = count($anchorTexts);

        return [
            'unique_anchor_texts' => count($uniqueTexts),
            'total_anchor_texts' => $totalTexts,
            'diversity_ratio' => $totalTexts > 0 ? round(count($uniqueTexts) / $totalTexts, 3) : 0,
            'diversity_level' => $this->getDiversityLevel(count($uniqueTexts), $totalTexts)
        ];
    }

    private function isAuthorityDomain(string $domain): bool
    {
        if (!$domain) return false;

        $authorityDomains = [
            'wikipedia.org', 'google.com', 'youtube.com', 'facebook.com',
            'twitter.com', 'linkedin.com', 'github.com', 'stackoverflow.com',
            'mozilla.org', 'w3.org', 'ietf.org'
        ];

        foreach ($authorityDomains as $authorityDomain) {
            if (str_ends_with($domain, $authorityDomain)) {
                return true;
            }
        }

        return false;
    }

    private function isPotentialSpamDomain(string $domain): bool
    {
        if (!$domain) return false;

        // Simple heuristics for spam detection
        $suspiciousPatterns = [
            '/[0-9]{5,}/', // Many numbers
            '/[a-z]{1}[0-9]{5,}/', // Single letter followed by many numbers
            '/[a-z0-9]{15,}/', // Very long random strings
            '/\.(tk|ml|ga|cf)$/' // Common spam TLDs
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $domain)) {
                return true;
            }
        }

        return false;
    }

    private function calculateDomainSafetyScore(string $domain, string $href): float
    {
        $score = 100;

        if ($this->isPotentialSpamDomain($domain)) {
            $score -= 50;
        }

        if (!str_starts_with($href, 'https://')) {
            $score -= 20;
        }

        if (str_contains($domain, 'bit.ly') || str_contains($domain, 'tinyurl')) {
            $score -= 30; // URL shorteners can hide malicious content
        }

        return max(0, min(100, $score));
    }

    private function analyzeDomainDiversity(array $domainCounts): array
    {
        $totalLinks = array_sum($domainCounts);
        $uniqueDomains = count($domainCounts);

        $diversityScore = $totalLinks > 0 ? ($uniqueDomains / $totalLinks) * 100 : 0;

        return [
            'unique_domains' => $uniqueDomains,
            'total_external_links' => $totalLinks,
            'diversity_score' => round($diversityScore, 1),
            'diversity_level' => $this->getDiversityLevel($uniqueDomains, $totalLinks),
            'domain_distribution' => $domainCounts,
            'most_linked_domain' => $this->getMostLinkedDomain($domainCounts)
        ];
    }

    private function analyzeDomainSafety(array $linkAnalysis): array
    {
        $safeLinks = count(array_filter($linkAnalysis, fn($link) => $link['safety_score'] >= 80));
        $riskyLinks = count(array_filter($linkAnalysis, fn($link) => $link['safety_score'] < 50));

        return [
            'safe_links' => $safeLinks,
            'risky_links' => $riskyLinks,
            'safety_percentage' => count($linkAnalysis) > 0 ? round(($safeLinks / count($linkAnalysis)) * 100, 1) : 0,
            'overall_safety' => $this->calculateOverallSafety($linkAnalysis)
        ];
    }

    private function isGenericAnchorText(string $text): bool
    {
        $genericTerms = [
            'click here', 'read more', 'more info', 'learn more', 'here',
            'this', 'link', 'website', 'page', 'article', 'post', 'more',
            'continue reading', 'find out more', 'details', 'info'
        ];

        return in_array(strtolower(trim($text)), $genericTerms);
    }

    private function containsLikelyKeywords(string $text): bool
    {
        return str_word_count($text) >= 2 && !$this->isGenericAnchorText($text);
    }

    private function calculateAnchorTextQuality(string $text): float
    {
        $score = 100;

        if (empty($text)) return 0;

        if ($this->isGenericAnchorText($text)) {
            $score -= 50;
        }

        $length = strlen($text);
        if ($length < 3) {
            $score -= 40;
        } elseif ($length > 60) {
            $score -= 20;
        }

        $wordCount = str_word_count($text);
        if ($wordCount < 2) {
            $score -= 20;
        } elseif ($wordCount >= 3) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function calculateTextDiversity(array $anchorTexts): array
    {
        $textCounts = array_count_values($anchorTexts);
        $duplicates = array_filter($textCounts, fn($count) => $count > 1);

        return [
            'total_texts' => count($anchorTexts),
            'unique_texts' => count($textCounts),
            'duplicate_texts' => count($duplicates),
            'diversity_ratio' => count($anchorTexts) > 0 ? round(count($textCounts) / count($anchorTexts), 3) : 0
        ];
    }

    private function getTopKeywords(array $keywordFrequency, int $limit): array
    {
        arsort($keywordFrequency);
        return array_slice($keywordFrequency, 0, $limit, true);
    }

    private function assessKeywordStuffingRisk(array $keywordFrequency, int $totalTexts): array
    {
        $riskLevel = 'low';
        $riskFactors = [];

        foreach ($keywordFrequency as $keyword => $frequency) {
            $percentage = ($frequency / $totalTexts) * 100;

            if ($percentage > 30) {
                $riskLevel = 'high';
                $riskFactors[] = "'{$keyword}' appears in {$percentage}% of anchor texts";
            } elseif ($percentage > 20) {
                $riskLevel = 'medium';
                $riskFactors[] = "'{$keyword}' frequently used in anchor texts";
            }
        }

        return [
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'recommendation' => $this->getKeywordStuffingRecommendation($riskLevel)
        ];
    }

    private function hasExternalIndication(array $link, string $html): bool
    {
        // Check if external links are properly indicated
        $href = $link['href'] ?? '';
        $isExternal = $link['is_external'] ?? false;

        if (!$isExternal) return true;

        // Look for visual indicators (simplified check)
        return str_contains($html, 'external') || str_contains($html, 'icon');
    }

    private function identifyAccessibilityIssues(array $checks, string $anchorText, string $href): array
    {
        $issues = [];

        if (!$checks['has_anchor_text']) {
            $issues[] = 'Missing anchor text';
        }
        if (!$checks['anchor_text_descriptive']) {
            $issues[] = 'Anchor text not descriptive enough';
        }
        if (!$checks['not_click_here']) {
            $issues[] = 'Uses generic "click here" type text';
        }
        if (!$checks['external_indication']) {
            $issues[] = 'External link not clearly indicated';
        }

        return $issues;
    }

    private function calculateLinkDensity(array $links, string $html): float
    {
        $textContent = strip_tags($html);
        $textLength = strlen($textContent);

        if ($textLength === 0) return 0;

        $linkTextLength = 0;
        foreach ($links as $link) {
            $linkTextLength += strlen($link['anchor_text'] ?? '');
        }

        return $linkTextLength / $textLength;
    }

    private function getTypeDistribution(array $links): array
    {
        $distribution = [
            'internal' => 0,
            'external' => 0,
            'anchor' => 0,
            'mailto' => 0,
            'tel' => 0,
            'other' => 0
        ];

        foreach ($links as $link) {
            $href = $link['href'] ?? '';

            if (str_starts_with($href, '#')) {
                $distribution['anchor']++;
            } elseif (str_starts_with($href, 'mailto:')) {
                $distribution['mailto']++;
            } elseif (str_starts_with($href, 'tel:')) {
                $distribution['tel']++;
            } elseif ($link['is_external'] ?? false) {
                $distribution['external']++;
            } elseif (!empty($href)) {
                $distribution['internal']++;
            } else {
                $distribution['other']++;
            }
        }

        return $distribution;
    }

    private function analyzePlacementPatterns(array $links, string $html): array
    {
        // Simplified placement analysis
        return [
            'in_navigation' => $this->countLinksInNavigation($html),
            'in_content' => $this->countLinksInContent($html),
            'in_footer' => $this->countLinksInFooter($html),
            'placement_quality' => 'Good' // Simplified
        ];
    }

    private function analyzeBalance(array $typeDistribution): array
    {
        $internal = $typeDistribution['internal'];
        $external = $typeDistribution['external'];

        $ratio = $external > 0 ? $internal / $external : ($internal > 0 ? 99 : 0);

        return [
            'internal_count' => $internal,
            'external_count' => $external,
            'internal_external_ratio' => round($ratio, 2),
            'balance_quality' => $this->getBalanceQuality($ratio),
            'recommendation' => $this->getBalanceRecommendation($ratio)
        ];
    }

    private function generateDistributionRecommendations(array $typeDistribution, array $balanceAnalysis): array
    {
        $recommendations = [];

        if ($typeDistribution['internal'] < 3) {
            $recommendations[] = 'Add more internal links for better site navigation and SEO';
        }

        if ($typeDistribution['external'] === 0) {
            $recommendations[] = 'Consider adding relevant external links to authoritative sources';
        }

        if ($balanceAnalysis['internal_external_ratio'] > 10) {
            $recommendations[] = 'Consider adding more external links for better content credibility';
        }

        return $recommendations;
    }

    private function assessManipulationRisk(array $links): array
    {
        $riskFactors = [];
        $riskLevel = 'low';

        // Check for excessive exact match anchor text
        $anchorTexts = array_filter(array_column($links, 'anchor_text'));
        $anchorCounts = array_count_values($anchorTexts);

        foreach ($anchorCounts as $text => $count) {
            if ($count > 5 && count($anchorTexts) > 10) {
                $riskFactors[] = "Exact anchor text '{$text}' used {$count} times";
                $riskLevel = 'medium';
            }
        }

        // Check for suspicious link patterns
        $externalLinks = array_filter($links, fn($link) => $link['is_external'] ?? false);
        if (count($externalLinks) > 20) {
            $riskFactors[] = 'High number of external links may indicate link schemes';
            $riskLevel = 'high';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'issues' => $riskLevel === 'high' ? ['Potential link manipulation detected'] : []
        ];
    }

    // Quality assessment helper methods

    private function getLinkQualityLevel(float $score): string
    {
        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 55) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Very Poor';
    }

    private function getAccessibilityComplianceLevel(float $score): string
    {
        if ($score >= 85) return 'WCAG AA Compliant';
        if ($score >= 70) return 'Mostly Compliant';
        if ($score >= 50) return 'Partially Compliant';
        return 'Non-Compliant';
    }

    private function getDistributionQuality(float $score): string
    {
        if ($score >= 80) return 'Well Balanced';
        if ($score >= 60) return 'Adequately Balanced';
        if ($score >= 40) return 'Poorly Balanced';
        return 'Very Poorly Balanced';
    }

    private function getSecurityLevel(float $score): string
    {
        if ($score >= 90) return 'Highly Secure';
        if ($score >= 75) return 'Secure';
        if ($score >= 60) return 'Moderately Secure';
        if ($score >= 40) return 'Low Security';
        return 'Security Risk';
    }

    private function getLinkGrade(float $score): string
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
        return 'D or lower';
    }

    private function getDiversityLevel(int $unique, int $total): string
    {
        if ($total === 0) return 'N/A';
        $ratio = $unique / $total;
        if ($ratio >= 0.8) return 'High';
        if ($ratio >= 0.6) return 'Medium';
        if ($ratio >= 0.4) return 'Low';
        return 'Very Low';
    }

    private function getMostLinkedDomain(array $domainCounts): ?string
    {
        if (empty($domainCounts)) return null;
        arsort($domainCounts);
        return array_key_first($domainCounts);
    }

    private function calculateOverallSafety(array $linkAnalysis): string
    {
        if (empty($linkAnalysis)) return 'N/A';

        $averageSafety = array_sum(array_column($linkAnalysis, 'safety_score')) / count($linkAnalysis);

        if ($averageSafety >= 80) return 'Safe';
        if ($averageSafety >= 60) return 'Mostly Safe';
        if ($averageSafety >= 40) return 'Moderate Risk';
        return 'High Risk';
    }

    private function getKeywordStuffingRecommendation(string $riskLevel): string
    {
        return match($riskLevel) {
            'high' => 'Diversify anchor text to avoid keyword stuffing penalties',
            'medium' => 'Consider varying anchor text more to appear natural',
            default => 'Anchor text diversity appears natural'
        };
    }

    private function determineWCAGLinkCompliance(float $score, array $accessibilityChecks): string
    {
        $criticalFailures = count(array_filter($accessibilityChecks, fn($check) => $check['accessibility_score'] < 50));

        if ($criticalFailures === 0 && $score >= 85) {
            return 'Pass';
        } elseif ($criticalFailures <= 1 && $score >= 70) {
            return 'Conditional Pass';
        } else {
            return 'Fail';
        }
    }

    // Additional helper methods for HTML analysis

    private function hasBreadcrumbs(string $html): bool
    {
        return str_contains($html, 'breadcrumb') ||
               str_contains($html, 'BreadcrumbList') ||
               preg_match('/>\s*›\s*<|>\s*\/\s*<|>\s*>\s*</', $html);
    }

    private function hasFooterLinks(string $html): bool
    {
        return preg_match('/<footer[^>]*>.*?<a[^>]*>/is', $html);
    }

    private function assessNavigationQuality(array $navigationLinks): string
    {
        $count = count($navigationLinks);
        if ($count >= 5) return 'Good';
        if ($count >= 3) return 'Fair';
        if ($count >= 1) return 'Poor';
        return 'None';
    }

    private function countLinksInNavigation(string $html): int
    {
        preg_match_all('/<nav[^>]*>.*?<\/nav>/is', $html, $navMatches);
        $count = 0;
        foreach ($navMatches[0] as $nav) {
            $count += substr_count($nav, '<a ');
        }
        return $count;
    }

    private function countLinksInContent(string $html): int
    {
        // Simplified - count links in main content areas
        preg_match_all('/<main[^>]*>.*?<\/main>|<article[^>]*>.*?<\/article>|<div[^>]*class="[^"]*content[^"]*"[^>]*>.*?<\/div>/is', $html, $contentMatches);
        $count = 0;
        foreach ($contentMatches[0] as $content) {
            $count += substr_count($content, '<a ');
        }
        return $count ?: substr_count($html, '<a '); // Fallback to all links
    }

    private function countLinksInFooter(string $html): int
    {
        preg_match_all('/<footer[^>]*>.*?<\/footer>/is', $html, $footerMatches);
        $count = 0;
        foreach ($footerMatches[0] as $footer) {
            $count += substr_count($footer, '<a ');
        }
        return $count;
    }

    private function getBalanceQuality(float $ratio): string
    {
        if ($ratio >= 1 && $ratio <= 5) return 'Good';
        if ($ratio >= 0.5 && $ratio <= 10) return 'Fair';
        return 'Poor';
    }

    private function getBalanceRecommendation(float $ratio): string
    {
        if ($ratio > 10) return 'Add more external links for credibility';
        if ($ratio < 0.5) return 'Add more internal links for better navigation';
        return 'Link balance is appropriate';
    }

    private function calculateInternalLinkingStrength(int $internalCount): string
    {
        if ($internalCount >= 10) return 'Strong';
        if ($internalCount >= 5) return 'Moderate';
        if ($internalCount >= 2) return 'Weak';
        return 'Very Weak';
    }

    private function evaluateExternalLinkingStrategy(int $externalCount, int $nofollowCount): string
    {
        if ($externalCount === 0) return 'No external linking';

        $nofollowRatio = $nofollowCount / $externalCount;

        if ($nofollowRatio >= 0.7) return 'Conservative (high nofollow usage)';
        if ($nofollowRatio >= 0.3) return 'Balanced';
        return 'Liberal (low nofollow usage)';
    }

    private function analyzeLinkEquityDistribution(array $linkCategories): array
    {
        $totalLinks = count($linkCategories['internal']) + count($linkCategories['external']);

        return [
            'internal_percentage' => $totalLinks > 0 ? round((count($linkCategories['internal']) / $totalLinks) * 100, 1) : 0,
            'external_percentage' => $totalLinks > 0 ? round((count($linkCategories['external']) / $totalLinks) * 100, 1) : 0,
            'equity_flow' => $this->assessEquityFlow($linkCategories)
        ];
    }

    private function assessEquityFlow(array $linkCategories): string
    {
        $internal = count($linkCategories['internal']);
        $external = count($linkCategories['external']);
        $externalNofollow = count(array_filter($linkCategories['external'], fn($link) => $link['is_nofollow'] ?? false));

        $equityLeaking = $external - $externalNofollow;

        if ($equityLeaking === 0) return 'Well preserved';
        if ($equityLeaking <= 3) return 'Minimal leakage';
        if ($equityLeaking <= 6) return 'Moderate leakage';
        return 'High leakage';
    }

    private function generateSecurityRecommendations(array $securityIssues): array
    {
        $recommendations = [];

        if (!empty($securityIssues)) {
            $recommendations[] = 'Add rel="noopener noreferrer" to external links opening in new tabs';
            $recommendations[] = 'Review suspicious URLs for potential security risks';
        } else {
            $recommendations[] = 'Link security practices are good';
        }

        return $recommendations;
    }

    private function generateLinkSummary(array $linkCategories, array $linksData): array
    {
        return [
            'total_links' => array_sum(array_map('count', $linkCategories)),
            'internal_links' => count($linkCategories['internal']),
            'external_links' => count($linkCategories['external']),
            'anchor_links' => count($linkCategories['anchor']),
            'mailto_links' => count($linkCategories['mailto']),
            'tel_links' => count($linkCategories['tel']),
            'nofollow_links' => $linksData['nofollow_count'] ?? 0,
            'empty_anchor_count' => $linksData['empty_anchor_count'] ?? 0
        ];
    }

    private function generateLinkRecommendations(array $analyses): array
    {
        $recommendations = [];

        // Internal link recommendations
        $internal = $analyses['internal'];
        if ($internal['score'] < 70) {
            foreach ($internal['issues'] as $issue) {
                $recommendations[] = [
                    'type' => str_contains($issue, 'Empty anchor') ? 'error' : 'warning',
                    'category' => 'internal_linking',
                    'message' => $issue,
                    'impact' => 'medium',
                    'fix' => $this->getInternalLinkFix($issue)
                ];
            }
        }

        // External link recommendations
        $external = $analyses['external'];
        if (!empty($external['safety_issues'])) {
            foreach ($external['safety_issues'] as $issue) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'external_linking',
                    'message' => $issue,
                    'impact' => 'medium',
                    'fix' => 'Review external links for quality and relevance'
                ];
            }
        }

        // Anchor text recommendations
        $anchorText = $analyses['anchor_text'];
        if ($anchorText['score'] < 70) {
            foreach ($anchorText['issues'] as $issue) {
                $recommendations[] = [
                    'type' => str_contains($issue, 'Empty') ? 'error' : 'suggestion',
                    'category' => 'anchor_text',
                    'message' => $issue,
                    'impact' => str_contains($issue, 'Generic') ? 'medium' : 'low',
                    'fix' => $this->getAnchorTextFix($issue)
                ];
            }
        }

        // Security recommendations
        $security = $analyses['security'];
        if (!empty($security['issues'])) {
            foreach ($security['issues'] as $issue) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'security',
                    'message' => $issue,
                    'impact' => 'medium',
                    'fix' => 'Add appropriate security attributes to external links'
                ];
            }
        }

        return $recommendations;
    }

    private function getInternalLinkFix(string $issue): string
    {
        if (str_contains($issue, 'Empty anchor')) {
            return 'Add descriptive anchor text to all internal links';
        } elseif (str_contains($issue, 'Non-descriptive')) {
            return 'Make anchor text more specific and descriptive';
        } elseif (str_contains($issue, 'linked') && str_contains($issue, 'times')) {
            return 'Reduce repetitive linking to the same URL';
        }
        return 'Improve internal linking structure and anchor text quality';
    }

    private function getAnchorTextFix(string $issue): string
    {
        if (str_contains($issue, 'Generic')) {
            return 'Replace generic terms like "click here" with descriptive anchor text';
        } elseif (str_contains($issue, 'too short')) {
            return 'Use more descriptive anchor text (minimum 3 characters)';
        } elseif (str_contains($issue, 'too long')) {
            return 'Shorten anchor text to be more concise (maximum 60 characters)';
        } elseif (str_contains($issue, 'keyword stuffing')) {
            return 'Diversify anchor text to avoid over-optimization';
        }
        return 'Improve anchor text quality and descriptiveness';
    }

    private function generateLinkInsights(array $overallScore, array $linkCategories, array $anchorTextAnalysis): array
    {
        $insights = [];

        if ($overallScore['overall'] >= 85) {
            $insights[] = 'Excellent link structure supports both SEO and user experience';
        } elseif ($overallScore['overall'] >= 70) {
            $insights[] = 'Good link implementation with room for optimization';
        } else {
            $insights[] = 'Link structure needs significant improvement for better SEO and usability';
        }

        $internalCount = count($linkCategories['internal']);
        $externalCount = count($linkCategories['external']);

        if ($internalCount === 0) {
            $insights[] = 'No internal links found - missing important navigation and SEO benefits';
        } elseif ($internalCount >= 5) {
            $insights[] = 'Good internal linking supports site navigation and link equity distribution';
        }

        if ($externalCount === 0) {
            $insights[] = 'No external links - consider linking to authoritative sources for credibility';
        } elseif ($externalCount > 15) {
            $insights[] = 'High number of external links may dilute page authority';
        }

        if ($anchorTextAnalysis['diversity_analysis']['diversity_ratio'] < 0.5) {
            $insights[] = 'Low anchor text diversity may indicate over-optimization';
        }

        return $insights;
    }
}