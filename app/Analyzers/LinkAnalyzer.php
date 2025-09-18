<?php

namespace App\Analyzers;

use App\DTOs\CrawlResult;
use DOMDocument;
use DOMXPath;

class LinkAnalyzer
{
    /**
     * Analyze link structure and content hierarchy
     */
    public function analyze(CrawlResult $crawlResult): array
    {
        if (!$crawlResult->isSuccessful() || empty($crawlResult->getContent())) {
            return $this->getEmptyResult();
        }

        $content = $crawlResult->getContent();
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new DOMXPath($dom);

        // Get the analyzed URL for internal/external link detection
        $analyzedUrl = $crawlResult->getUrl();
        $parsedUrl = parse_url($analyzedUrl);
        $baseDomain = $parsedUrl['host'] ?? '';

        return [
            'links_analysis' => $this->analyzeLinks($xpath, $baseDomain),
            'heading_structure' => $this->analyzeHeadingStructure($xpath),
            'image_analysis' => $this->analyzeImages($xpath),
            'content_structure' => $this->analyzeContentStructure($xpath),
            'anchor_text_analysis' => $this->analyzeAnchorText($xpath, $baseDomain),
            'link_distribution' => $this->analyzeLinkDistribution($xpath, $baseDomain),
            'recommendations' => [],
        ];
    }

    /**
     * Analyze all links on the page
     */
    private function analyzeLinks(DOMXPath $xpath, string $baseDomain): array
    {
        $linkNodes = $xpath->query('//a[@href]');
        $internalLinks = [];
        $externalLinks = [];
        $emailLinks = [];
        $phoneLinks = [];
        $anchorLinks = [];
        $noFollowLinks = [];

        foreach ($linkNodes as $link) {
            $href = trim($link->getAttribute('href'));
            $text = trim($link->textContent);
            $rel = $link->getAttribute('rel');
            $title = $link->getAttribute('title');

            if (empty($href)) {
                continue;
            }

            $linkData = [
                'url' => $href,
                'text' => $text,
                'title' => $title,
                'rel' => $rel,
                'is_nofollow' => strpos($rel, 'nofollow') !== false,
                'is_external' => false,
                'domain' => '',
            ];

            // Categorize links
            if (strpos($href, 'mailto:') === 0) {
                $emailLinks[] = $linkData;
            } elseif (strpos($href, 'tel:') === 0) {
                $phoneLinks[] = $linkData;
            } elseif (strpos($href, '#') === 0) {
                $anchorLinks[] = $linkData;
            } else {
                // Parse URL to determine if internal or external
                $parsedHref = parse_url($href);
                
                if (isset($parsedHref['host'])) {
                    $linkDomain = $parsedHref['host'];
                    $linkData['domain'] = $linkDomain;
                    
                    if ($linkDomain !== $baseDomain) {
                        $linkData['is_external'] = true;
                        $externalLinks[] = $linkData;
                    } else {
                        $internalLinks[] = $linkData;
                    }
                } else {
                    // Relative URL, treat as internal
                    $internalLinks[] = $linkData;
                }

                if (strpos($rel, 'nofollow') !== false) {
                    $noFollowLinks[] = $linkData;
                }
            }
        }

        return [
            'total_links' => $linkNodes->length,
            'internal_links' => $internalLinks,
            'external_links' => $externalLinks,
            'email_links' => $emailLinks,
            'phone_links' => $phoneLinks,
            'anchor_links' => $anchorLinks,
            'nofollow_links' => $noFollowLinks,
            'internal_count' => count($internalLinks),
            'external_count' => count($externalLinks),
            'email_count' => count($emailLinks),
            'phone_count' => count($phoneLinks),
            'anchor_count' => count($anchorLinks),
            'nofollow_count' => count($noFollowLinks),
        ];
    }

    /**
     * Analyze heading structure (H1-H6)
     */
    private function analyzeHeadingStructure(DOMXPath $xpath): array
    {
        $headings = [];
        $headingCounts = ['h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0];
        $issues = [];

        for ($level = 1; $level <= 6; $level++) {
            $headingNodes = $xpath->query("//h{$level}");
            $headingCounts["h{$level}"] = $headingNodes->length;

            foreach ($headingNodes as $heading) {
                $headings[] = [
                    'level' => $level,
                    'text' => trim($heading->textContent),
                    'length' => mb_strlen(trim($heading->textContent)),
                    'has_keywords' => false, // Will be set by keyword analyzer if needed
                ];
            }
        }

        // Check for heading structure issues
        if ($headingCounts['h1'] === 0) {
            $issues[] = 'Missing H1 tag';
        } elseif ($headingCounts['h1'] > 1) {
            $issues[] = 'Multiple H1 tags found (should be only one)';
        }

        // Check for proper heading hierarchy
        $previousLevel = 0;
        foreach ($headings as $heading) {
            $currentLevel = $heading['level'];
            if ($previousLevel > 0 && $currentLevel > $previousLevel + 1) {
                $issues[] = "Heading hierarchy issue: H{$currentLevel} follows H{$previousLevel} (skipped levels)";
            }
            $previousLevel = $currentLevel;
        }

        return [
            'headings' => $headings,
            'counts' => $headingCounts,
            'total_headings' => array_sum($headingCounts),
            'issues' => $issues,
            'has_proper_structure' => empty($issues),
        ];
    }

    /**
     * Analyze images and their SEO attributes
     */
    private function analyzeImages(DOMXPath $xpath): array
    {
        $imageNodes = $xpath->query('//img');
        $images = [];
        $issues = [];
        $missingAltCount = 0;
        $emptyAltCount = 0;

        foreach ($imageNodes as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $title = $img->getAttribute('title');
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');

            $imageData = [
                'src' => $src,
                'alt' => $alt,
                'title' => $title,
                'width' => $width,
                'height' => $height,
                'has_alt' => !empty($alt),
                'alt_length' => mb_strlen($alt),
                'is_decorative' => $alt === '', // Empty alt="" indicates decorative image
            ];

            if (empty($alt) && $img->hasAttribute('alt') === false) {
                $missingAltCount++;
                $imageData['issue'] = 'Missing alt attribute';
            } elseif (empty($alt) && $img->hasAttribute('alt')) {
                $emptyAltCount++;
                $imageData['issue'] = 'Empty alt attribute (decorative image)';
            } elseif (mb_strlen($alt) > 125) {
                $imageData['issue'] = 'Alt text too long (should be under 125 characters)';
            }

            $images[] = $imageData;
        }

        if ($missingAltCount > 0) {
            $issues[] = "{$missingAltCount} images missing alt attributes";
        }

        return [
            'images' => $images,
            'total_images' => $imageNodes->length,
            'missing_alt_count' => $missingAltCount,
            'empty_alt_count' => $emptyAltCount,
            'images_with_alt' => $imageNodes->length - $missingAltCount,
            'issues' => $issues,
            'alt_text_optimization' => $this->analyzeAltTextOptimization($images),
        ];
    }

    /**
     * Analyze content structure and organization
     */
    private function analyzeContentStructure(DOMXPath $xpath): array
    {
        $paragraphs = $xpath->query('//p');
        $lists = $xpath->query('//ul | //ol');
        $listItems = $xpath->query('//li');
        $tables = $xpath->query('//table');
        $blockquotes = $xpath->query('//blockquote');

        // Analyze paragraph lengths
        $paragraphLengths = [];
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if (!empty($text)) {
                $paragraphLengths[] = str_word_count($text);
            }
        }

        $avgParagraphLength = count($paragraphLengths) > 0 ? array_sum($paragraphLengths) / count($paragraphLengths) : 0;

        return [
            'paragraph_count' => $paragraphs->length,
            'list_count' => $lists->length,
            'list_item_count' => $listItems->length,
            'table_count' => $tables->length,
            'blockquote_count' => $blockquotes->length,
            'avg_paragraph_length' => round($avgParagraphLength, 1),
            'paragraph_lengths' => $paragraphLengths,
            'content_organization' => $this->evaluateContentOrganization($paragraphs, $lists, $xpath),
        ];
    }

    /**
     * Analyze anchor text distribution and optimization
     */
    private function analyzeAnchorText(DOMXPath $xpath, string $baseDomain): array
    {
        $linkNodes = $xpath->query('//a[@href]');
        $anchorTexts = [];
        $exactMatches = [];
        $genericTexts = ['click here', 'read more', 'more', 'here', 'link', 'continue reading'];

        foreach ($linkNodes as $link) {
            $href = trim($link->getAttribute('href'));
            $text = trim($link->textContent);
            
            if (empty($href) || empty($text)) {
                continue;
            }

            // Skip anchor links and email/phone links
            if (strpos($href, '#') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) {
                continue;
            }

            $isInternal = $this->isInternalLink($href, $baseDomain);
            $textLower = mb_strtolower($text);

            $anchorData = [
                'text' => $text,
                'href' => $href,
                'length' => mb_strlen($text),
                'is_internal' => $isInternal,
                'is_generic' => in_array($textLower, $genericTexts),
                'word_count' => str_word_count($text),
            ];

            $anchorTexts[] = $anchorData;

            // Track exact matches for duplicate detection
            $textKey = $textLower . '|' . $href;
            if (!isset($exactMatches[$textKey])) {
                $exactMatches[$textKey] = 0;
            }
            $exactMatches[$textKey]++;
        }

        // Find duplicates
        $duplicates = array_filter($exactMatches, function($count) {
            return $count > 1;
        });

        $genericCount = count(array_filter($anchorTexts, function($anchor) {
            return $anchor['is_generic'];
        }));

        return [
            'anchor_texts' => $anchorTexts,
            'total_anchor_texts' => count($anchorTexts),
            'generic_anchor_count' => $genericCount,
            'duplicate_anchor_count' => count($duplicates),
            'avg_anchor_length' => count($anchorTexts) > 0 ? round(array_sum(array_column($anchorTexts, 'length')) / count($anchorTexts), 1) : 0,
            'optimization_score' => $this->calculateAnchorOptimizationScore($anchorTexts, $genericCount, count($duplicates)),
        ];
    }

    /**
     * Analyze link distribution throughout the page
     */
    private function analyzeLinkDistribution(DOMXPath $xpath, string $baseDomain): array
    {
        // Analyze links in different page sections
        $headerLinks = $this->countLinksInSection($xpath, '//header//a[@href]', $baseDomain);
        $navLinks = $this->countLinksInSection($xpath, '//nav//a[@href]', $baseDomain);
        $mainLinks = $this->countLinksInSection($xpath, '//main//a[@href] | //article//a[@href]', $baseDomain);
        $footerLinks = $this->countLinksInSection($xpath, '//footer//a[@href]', $baseDomain);
        $sidebarLinks = $this->countLinksInSection($xpath, '//aside//a[@href] | //*[@class="sidebar"]//a[@href]', $baseDomain);

        return [
            'header_links' => $headerLinks,
            'navigation_links' => $navLinks,
            'main_content_links' => $mainLinks,
            'footer_links' => $footerLinks,
            'sidebar_links' => $sidebarLinks,
            'content_to_navigation_ratio' => $navLinks['total'] > 0 ? round($mainLinks['total'] / $navLinks['total'], 2) : 0,
        ];
    }

    /**
     * Count links in a specific page section
     */
    private function countLinksInSection(DOMXPath $xpath, string $query, string $baseDomain): array
    {
        $links = $xpath->query($query);
        $internal = 0;
        $external = 0;

        foreach ($links as $link) {
            $href = trim($link->getAttribute('href'));
            if (empty($href) || strpos($href, '#') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) {
                continue;
            }

            if ($this->isInternalLink($href, $baseDomain)) {
                $internal++;
            } else {
                $external++;
            }
        }

        return [
            'total' => $links->length,
            'internal' => $internal,
            'external' => $external,
        ];
    }

    /**
     * Check if a link is internal
     */
    private function isInternalLink(string $href, string $baseDomain): bool
    {
        $parsedHref = parse_url($href);
        
        if (!isset($parsedHref['host'])) {
            // Relative URL, treat as internal
            return true;
        }
        
        return $parsedHref['host'] === $baseDomain;
    }

    /**
     * Analyze alt text optimization
     */
    private function analyzeAltTextOptimization(array $images): array
    {
        $totalImages = count($images);
        $optimizedCount = 0;
        $issues = [];

        foreach ($images as $image) {
            if (!empty($image['alt']) && mb_strlen($image['alt']) >= 5 && mb_strlen($image['alt']) <= 125) {
                $optimizedCount++;
            }
        }

        $optimizationPercentage = $totalImages > 0 ? ($optimizedCount / $totalImages) * 100 : 0;

        return [
            'total_images' => $totalImages,
            'optimized_images' => $optimizedCount,
            'optimization_percentage' => round($optimizationPercentage, 1),
            'needs_improvement' => $optimizationPercentage < 80,
        ];
    }

    /**
     * Evaluate content organization
     */
    private function evaluateContentOrganization(object $paragraphs, object $lists, DOMXPath $xpath): array
    {
        $score = 0;
        $feedback = [];

        // Check for proper use of lists
        if ($lists->length > 0) {
            $score += 20;
            $feedback[] = 'Good use of lists for content organization';
        }

        // Check paragraph length distribution
        $longParagraphs = 0;
        foreach ($paragraphs as $p) {
            $wordCount = str_word_count($p->textContent);
            if ($wordCount > 150) {
                $longParagraphs++;
            }
        }

        if ($longParagraphs <= $paragraphs->length * 0.3) {
            $score += 30;
            $feedback[] = 'Good paragraph length distribution';
        } else {
            $feedback[] = 'Consider breaking up long paragraphs';
        }

        // Check for use of formatting elements
        $strongTags = $xpath->query('//strong | //b')->length;
        $emTags = $xpath->query('//em | //i')->length;
        
        if ($strongTags > 0 || $emTags > 0) {
            $score += 25;
            $feedback[] = 'Good use of text emphasis';
        }

        return [
            'organization_score' => min(100, $score),
            'feedback' => $feedback,
        ];
    }

    /**
     * Calculate anchor text optimization score
     */
    private function calculateAnchorOptimizationScore(array $anchorTexts, int $genericCount, int $duplicateCount): float
    {
        $totalAnchors = count($anchorTexts);
        
        if ($totalAnchors === 0) {
            return 0;
        }

        $score = 100;

        // Penalize generic anchor texts
        $genericPercentage = ($genericCount / $totalAnchors) * 100;
        $score -= $genericPercentage * 0.8;

        // Penalize duplicates
        $duplicatePercentage = ($duplicateCount / $totalAnchors) * 100;
        $score -= $duplicatePercentage * 0.5;

        // Check for appropriate anchor text length
        $goodLengthCount = count(array_filter($anchorTexts, function($anchor) {
            return $anchor['length'] >= 10 && $anchor['length'] <= 60;
        }));
        
        $goodLengthPercentage = ($goodLengthCount / $totalAnchors) * 100;
        $score = ($score * 0.7) + ($goodLengthPercentage * 0.3);

        return max(0, round($score, 1));
    }

    /**
     * Get empty result structure
     */
    private function getEmptyResult(): array
    {
        return [
            'links_analysis' => [
                'total_links' => 0,
                'internal_links' => [],
                'external_links' => [],
                'internal_count' => 0,
                'external_count' => 0,
            ],
            'heading_structure' => [
                'headings' => [],
                'counts' => ['h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0],
                'issues' => ['No content available'],
            ],
            'image_analysis' => [
                'images' => [],
                'total_images' => 0,
                'issues' => ['No content available'],
            ],
            'content_structure' => [],
            'anchor_text_analysis' => [],
            'link_distribution' => [],
            'recommendations' => ['No content available for analysis'],
        ];
    }
}