<?php

namespace App\Analyzers;

use App\DTOs\ContentStructureResult;
use App\DTOs\CrawlResult;
use App\DTOs\TextProcessingResult;
use DOMDocument;
use DOMXPath;

class ContentStructureAnalyzer
{
    public function analyze(CrawlResult $crawlResult, TextProcessingResult $textResult): ContentStructureResult
    {
        if (!$crawlResult->isSuccessful() || empty($crawlResult->htmlContent)) {
            return $this->createEmptyResult();
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($crawlResult->htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Analyze heading structure
        $headingStructure = $this->analyzeHeadingStructure($xpath);
        
        // Analyze links
        $linkAnalysis = $this->analyzeLinkStructure($crawlResult);
        
        // Analyze images
        $imageAnalysis = $this->analyzeImageOptimization($crawlResult);
        
        // Analyze content hierarchy
        $contentHierarchy = $this->analyzeContentHierarchy($xpath, $textResult);
        
        // Calculate content metrics
        $contentLength = $textResult->wordCount;
        $contentDepthScore = $this->calculateContentDepthScore($headingStructure, $contentLength, $textResult->paragraphCount);
        
        // Identify structure issues
        $structureIssues = $this->identifyStructureIssues($headingStructure, $linkAnalysis, $imageAnalysis);
        
        // Generate suggestions
        $suggestions = $this->generateStructureSuggestions($headingStructure, $linkAnalysis, $imageAnalysis, $structureIssues);

        return new ContentStructureResult(
            headingStructure: $headingStructure,
            linkAnalysis: $linkAnalysis,
            imageAnalysis: $imageAnalysis,
            contentHierarchy: $contentHierarchy,
            contentLength: $contentLength,
            contentDepthScore: $contentDepthScore,
            structureIssues: $structureIssues,
            suggestions: $suggestions
        );
    }

    private function createEmptyResult(): ContentStructureResult
    {
        return new ContentStructureResult(
            headingStructure: [],
            linkAnalysis: [],
            imageAnalysis: [],
            contentHierarchy: [],
            contentLength: 0,
            contentDepthScore: 0.0,
            structureIssues: ['Content analysis failed - no valid content found'],
            suggestions: ['Unable to analyze content structure']
        );
    }

    private function analyzeHeadingStructure(DOMXPath $xpath): array
    {
        $structure = [
            'hierarchy' => [],
            'counts' => [],
            'issues' => [],
            'quality_score' => 0,
        ];

        // Count headings by level
        for ($level = 1; $level <= 6; $level++) {
            $headings = $xpath->query("//h{$level}");
            $structure['counts']["h{$level}"] = $headings->length;
            
            // Collect heading texts
            $headingTexts = [];
            foreach ($headings as $heading) {
                $text = trim($heading->textContent);
                if (!empty($text)) {
                    $headingTexts[] = [
                        'text' => $text,
                        'length' => mb_strlen($text),
                        'word_count' => count(explode(' ', $text)),
                    ];
                }
            }
            
            if (!empty($headingTexts)) {
                $structure['hierarchy']["h{$level}"] = $headingTexts;
            }
        }

        // Analyze heading hierarchy issues
        $structure['issues'] = $this->findHeadingIssues($structure['counts']);
        
        // Calculate quality score
        $structure['quality_score'] = $this->calculateHeadingQualityScore($structure);

        return $structure;
    }

    private function findHeadingIssues(array $counts): array
    {
        $issues = [];

        // Check for H1 tags
        if (($counts['h1'] ?? 0) === 0) {
            $issues[] = 'No H1 tag found';
        } elseif (($counts['h1'] ?? 0) > 1) {
            $issues[] = 'Multiple H1 tags found - should have only one';
        }

        // Check hierarchy skipping
        $hasContent = false;
        $previousLevel = 0;
        
        for ($level = 1; $level <= 6; $level++) {
            $currentCount = $counts["h{$level}"] ?? 0;
            
            if ($currentCount > 0) {
                if ($hasContent && $level > $previousLevel + 1) {
                    $issues[] = "Heading hierarchy skips from H{$previousLevel} to H{$level}";
                }
                $hasContent = true;
                $previousLevel = $level;
            }
        }

        // Check for proper heading distribution
        $totalHeadings = array_sum($counts);
        if ($totalHeadings > 0) {
            $h2Count = $counts['h2'] ?? 0;
            if ($h2Count === 0 && $totalHeadings > 1) {
                $issues[] = 'No H2 tags found - consider adding section headings';
            }
        }

        return $issues;
    }

    private function calculateHeadingQualityScore(array $structure): float
    {
        $score = 0;
        $maxScore = 100;

        // H1 presence and uniqueness (30 points)
        $h1Count = $structure['counts']['h1'] ?? 0;
        if ($h1Count === 1) {
            $score += 30;
        } elseif ($h1Count > 1) {
            $score += 15; // Partial points for having H1 but multiple
        }

        // Proper hierarchy (25 points)
        $hierarchyIssues = count(array_filter($structure['issues'], function($issue) {
            return strpos($issue, 'hierarchy') !== false || strpos($issue, 'skips') !== false;
        }));
        
        if ($hierarchyIssues === 0) {
            $score += 25;
        } elseif ($hierarchyIssues === 1) {
            $score += 15;
        }

        // H2 usage (20 points)
        $h2Count = $structure['counts']['h2'] ?? 0;
        if ($h2Count >= 2) {
            $score += 20;
        } elseif ($h2Count === 1) {
            $score += 10;
        }

        // Heading text quality (25 points)
        $qualityScore = $this->assessHeadingTextQuality($structure['hierarchy']);
        $score += ($qualityScore / 100) * 25;

        return round(($score / $maxScore) * 100, 2);
    }

    private function assessHeadingTextQuality(array $hierarchy): float
    {
        $totalHeadings = 0;
        $qualityPoints = 0;

        foreach ($hierarchy as $level => $headings) {
            foreach ($headings as $heading) {
                $totalHeadings++;
                
                // Check length (optimal: 10-70 characters)
                $length = $heading['length'];
                if ($length >= 10 && $length <= 70) {
                    $qualityPoints += 25;
                } elseif ($length >= 5 && $length <= 100) {
                    $qualityPoints += 15;
                }

                // Check word count (optimal: 2-8 words)
                $wordCount = $heading['word_count'];
                if ($wordCount >= 2 && $wordCount <= 8) {
                    $qualityPoints += 25;
                } elseif ($wordCount >= 1 && $wordCount <= 12) {
                    $qualityPoints += 15;
                }

                // Check for descriptive content (not just numbers or single words)
                if ($wordCount >= 2 && !ctype_digit($heading['text'])) {
                    $qualityPoints += 25;
                }

                // Check for proper capitalization
                if (ucfirst($heading['text']) === $heading['text'] || ucwords($heading['text']) === $heading['text']) {
                    $qualityPoints += 25;
                }
            }
        }

        return $totalHeadings > 0 ? ($qualityPoints / ($totalHeadings * 100)) * 100 : 0;
    }

    private function analyzeLinkStructure(CrawlResult $crawlResult): array
    {
        $analysis = [
            'internal_count' => count($crawlResult->internalLinks),
            'external_count' => count($crawlResult->externalLinks),
            'internal_links' => $crawlResult->internalLinks,
            'external_links' => $crawlResult->externalLinks,
            'anchor_text_analysis' => [],
            'link_quality_score' => 0,
            'issues' => [],
        ];

        // Analyze anchor text
        $analysis['anchor_text_analysis'] = $this->analyzeAnchorText($crawlResult);
        
        // Calculate link quality score
        $analysis['link_quality_score'] = $this->calculateLinkQualityScore($analysis);
        
        // Identify link issues
        $analysis['issues'] = $this->identifyLinkIssues($analysis);

        return $analysis;
    }

    private function analyzeAnchorText(CrawlResult $crawlResult): array
    {
        $anchorAnalysis = [
            'descriptive_count' => 0,
            'generic_count' => 0,
            'empty_count' => 0,
            'total_count' => 0,
            'generic_texts' => [],
            'empty_links' => [],
        ];

        $genericPhrases = [
            'click here', 'read more', 'here', 'link', 'more', 'continue',
            'learn more', 'find out more', 'see more', 'view more'
        ];

        $allLinks = array_merge($crawlResult->internalLinks, $crawlResult->externalLinks);

        foreach ($allLinks as $link) {
            $anchorAnalysis['total_count']++;
            $text = trim(mb_strtolower($link['text'] ?? ''));

            if (empty($text)) {
                $anchorAnalysis['empty_count']++;
                $anchorAnalysis['empty_links'][] = $link['url'] ?? '';
            } elseif (in_array($text, $genericPhrases) || mb_strlen($text) <= 3) {
                $anchorAnalysis['generic_count']++;
                $anchorAnalysis['generic_texts'][] = $text;
            } else {
                $anchorAnalysis['descriptive_count']++;
            }
        }

        return $anchorAnalysis;
    }

    private function calculateLinkQualityScore(array $analysis): float
    {
        $score = 0;
        $maxScore = 100;

        $totalLinks = $analysis['internal_count'] + $analysis['external_count'];
        
        if ($totalLinks === 0) {
            return 0;
        }

        // Internal vs external balance (30 points)
        $internalRatio = $totalLinks > 0 ? $analysis['internal_count'] / $totalLinks : 0;
        if ($internalRatio >= 0.6 && $internalRatio <= 0.9) {
            $score += 30; // Good internal linking
        } elseif ($internalRatio >= 0.4) {
            $score += 20;
        }

        // Anchor text quality (50 points)
        $anchorAnalysis = $analysis['anchor_text_analysis'];
        if ($anchorAnalysis['total_count'] > 0) {
            $descriptiveRatio = $anchorAnalysis['descriptive_count'] / $anchorAnalysis['total_count'];
            $score += $descriptiveRatio * 50;
        }

        // Link quantity appropriateness (20 points)
        if ($totalLinks >= 2 && $totalLinks <= 10) {
            $score += 20; // Good number of links
        } elseif ($totalLinks >= 1 && $totalLinks <= 15) {
            $score += 15;
        } elseif ($totalLinks > 0) {
            $score += 10;
        }

        return round($score, 2);
    }

    private function identifyLinkIssues(array $analysis): array
    {
        $issues = [];

        // Check for no internal links
        if ($analysis['internal_count'] === 0) {
            $issues[] = 'No internal links found - consider adding links to related content';
        }

        // Check for poor anchor text
        $anchorAnalysis = $analysis['anchor_text_analysis'];
        if ($anchorAnalysis['total_count'] > 0) {
            $genericRatio = $anchorAnalysis['generic_count'] / $anchorAnalysis['total_count'];
            if ($genericRatio > 0.5) {
                $issues[] = 'High ratio of generic anchor text - use more descriptive link text';
            }

            if ($anchorAnalysis['empty_count'] > 0) {
                $issues[] = 'Links with empty anchor text found';
            }
        }

        // Check for too many external links
        $totalLinks = $analysis['internal_count'] + $analysis['external_count'];
        if ($totalLinks > 0) {
            $externalRatio = $analysis['external_count'] / $totalLinks;
            if ($externalRatio > 0.7) {
                $issues[] = 'High ratio of external links - consider balancing with internal links';
            }
        }

        return $issues;
    }

    private function analyzeImageOptimization(CrawlResult $crawlResult): array
    {
        $analysis = [
            'total_count' => count($crawlResult->images),
            'with_alt_count' => 0,
            'without_alt_count' => 0,
            'empty_alt_count' => 0,
            'descriptive_alt_count' => 0,
            'optimization_score' => 0,
            'issues' => [],
            'images_without_alt' => [],
        ];

        foreach ($crawlResult->images as $image) {
            $alt = trim($image['alt'] ?? '');
            
            if (empty($alt)) {
                $analysis['without_alt_count']++;
                $analysis['images_without_alt'][] = $image['src'] ?? '';
            } else {
                $analysis['with_alt_count']++;
                
                if (mb_strlen($alt) <= 3 || in_array(mb_strtolower($alt), ['image', 'img', 'photo', 'picture'])) {
                    $analysis['empty_alt_count']++;
                } else {
                    $analysis['descriptive_alt_count']++;
                }
            }
        }

        // Calculate optimization score
        if ($analysis['total_count'] > 0) {
            $analysis['optimization_score'] = ($analysis['descriptive_alt_count'] / $analysis['total_count']) * 100;
        }

        // Identify issues
        if ($analysis['without_alt_count'] > 0) {
            $analysis['issues'][] = "{$analysis['without_alt_count']} images missing alt text";
        }

        if ($analysis['empty_alt_count'] > 0) {
            $analysis['issues'][] = "{$analysis['empty_alt_count']} images with generic alt text";
        }

        return $analysis;
    }

    private function analyzeContentHierarchy(DOMXPath $xpath, TextProcessingResult $textResult): array
    {
        $hierarchy = [
            'sections' => [],
            'depth_levels' => 0,
            'content_distribution' => [],
            'structure_quality' => 0,
        ];

        // Analyze content sections based on headings
        $sections = [];
        $currentSection = null;
        
        // Get all content elements in document order
        $contentElements = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //p');
        
        foreach ($contentElements as $element) {
            $tagName = $element->tagName;
            
            if (preg_match('/^h[1-6]$/', $tagName)) {
                // Start new section
                if ($currentSection !== null) {
                    $sections[] = $currentSection;
                }
                
                $currentSection = [
                    'heading' => trim($element->textContent),
                    'level' => intval(substr($tagName, 1)),
                    'content_length' => 0,
                    'paragraphs' => 0,
                ];
            } elseif ($currentSection !== null && $tagName === 'p') {
                // Add content to current section
                $content = trim($element->textContent);
                $currentSection['content_length'] += mb_strlen($content);
                $currentSection['paragraphs']++;
            }
        }
        
        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }

        $hierarchy['sections'] = $sections;
        $hierarchy['depth_levels'] = $this->calculateDepthLevels($sections);
        $hierarchy['content_distribution'] = $this->analyzeContentDistribution($sections);
        $hierarchy['structure_quality'] = $this->calculateStructureQuality($hierarchy);

        return $hierarchy;
    }

    private function calculateDepthLevels(array $sections): int
    {
        $levels = array_map(function($section) {
            return $section['level'];
        }, $sections);

        return !empty($levels) ? max($levels) : 0;
    }

    private function analyzeContentDistribution(array $sections): array
    {
        if (empty($sections)) {
            return [];
        }

        $distribution = [];
        $totalContent = 0;

        foreach ($sections as $section) {
            $totalContent += $section['content_length'];
        }

        foreach ($sections as $section) {
            $percentage = $totalContent > 0 ? ($section['content_length'] / $totalContent) * 100 : 0;
            
            $distribution[] = [
                'heading' => $section['heading'],
                'level' => $section['level'],
                'content_percentage' => round($percentage, 2),
                'word_count_estimate' => intval($section['content_length'] / 5), // Rough estimate
            ];
        }

        return $distribution;
    }

    private function calculateStructureQuality(array $hierarchy): float
    {
        $score = 0;
        $maxScore = 100;

        // Check for proper sectioning (40 points)
        $sectionCount = count($hierarchy['sections']);
        if ($sectionCount >= 3 && $sectionCount <= 8) {
            $score += 40;
        } elseif ($sectionCount >= 2 && $sectionCount <= 10) {
            $score += 30;
        } elseif ($sectionCount >= 1) {
            $score += 20;
        }

        // Check content distribution balance (30 points)
        $distribution = $hierarchy['content_distribution'];
        if (!empty($distribution)) {
            $percentages = array_column($distribution, 'content_percentage');
            $maxPercentage = max($percentages);
            
            if ($maxPercentage <= 50) {
                $score += 30; // Well balanced
            } elseif ($maxPercentage <= 70) {
                $score += 20; // Fairly balanced
            } elseif ($maxPercentage <= 85) {
                $score += 10; // Somewhat unbalanced
            }
        }

        // Check hierarchy depth (30 points)
        $depthLevels = $hierarchy['depth_levels'];
        if ($depthLevels >= 2 && $depthLevels <= 4) {
            $score += 30; // Good depth
        } elseif ($depthLevels >= 1 && $depthLevels <= 5) {
            $score += 20; // Acceptable depth
        } elseif ($depthLevels >= 1) {
            $score += 10; // Some structure
        }

        return round($score, 2);
    }

    private function calculateContentDepthScore(array $headingStructure, int $contentLength, int $paragraphCount): float
    {
        $score = 0;
        $factors = [];

        // Content length factor (0-30 points)
        if ($contentLength >= 1000) {
            $score += 30;
            $factors[] = 'comprehensive_length';
        } elseif ($contentLength >= 500) {
            $score += 20;
            $factors[] = 'adequate_length';
        } elseif ($contentLength >= 300) {
            $score += 15;
            $factors[] = 'minimum_length';
        } else {
            $factors[] = 'short_content';
        }

        // Heading structure factor (0-40 points)
        $headingScore = $headingStructure['quality_score'] ?? 0;
        $score += ($headingScore / 100) * 40;

        // Paragraph structure factor (0-30 points)
        if ($paragraphCount >= 5) {
            $score += 30;
            $factors[] = 'well_structured';
        } elseif ($paragraphCount >= 3) {
            $score += 20;
            $factors[] = 'adequately_structured';
        } elseif ($paragraphCount >= 2) {
            $score += 15;
            $factors[] = 'basic_structure';
        } else {
            $factors[] = 'poor_structure';
        }

        return round($score, 2);
    }

    private function identifyStructureIssues(array $headingStructure, array $linkAnalysis, array $imageAnalysis): array
    {
        $issues = [];

        // Heading issues
        $issues = array_merge($issues, $headingStructure['issues'] ?? []);

        // Link issues
        $issues = array_merge($issues, $linkAnalysis['issues'] ?? []);

        // Image issues
        $issues = array_merge($issues, $imageAnalysis['issues'] ?? []);

        return array_unique($issues);
    }

    private function generateStructureSuggestions(
        array $headingStructure,
        array $linkAnalysis,
        array $imageAnalysis,
        array $structureIssues
    ): array {
        $suggestions = [];

        // Heading suggestions
        if (($headingStructure['quality_score'] ?? 0) < 70) {
            $suggestions[] = 'Improve heading structure by adding descriptive H2 and H3 tags';
        }

        if (($headingStructure['counts']['h1'] ?? 0) === 0) {
            $suggestions[] = 'Add a single H1 tag as the main page title';
        }

        // Link suggestions
        if (($linkAnalysis['link_quality_score'] ?? 0) < 60) {
            $suggestions[] = 'Improve link structure by adding internal links and using descriptive anchor text';
        }

        if ($linkAnalysis['internal_count'] === 0) {
            $suggestions[] = 'Add internal links to help users navigate to related content';
        }

        // Image suggestions
        if (($imageAnalysis['optimization_score'] ?? 0) < 80 && $imageAnalysis['total_count'] > 0) {
            $suggestions[] = 'Add descriptive alt text to images for better accessibility and SEO';
        }

        // General structure suggestions
        if (empty($structureIssues)) {
            $suggestions[] = 'Content structure looks good overall';
        } else {
            $suggestions[] = 'Address structure issues: ' . implode(', ', array_slice($structureIssues, 0, 3));
        }

        if (count($suggestions) === 0) {
            $suggestions[] = 'Consider adding more structural elements like headings and internal links';
        }

        return array_unique($suggestions);
    }
}