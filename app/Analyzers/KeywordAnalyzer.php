<?php

namespace App\Analyzers;

use App\DTOs\CrawlResult;
use App\Utils\TextProcessor;
use DOMDocument;
use DOMXPath;

class KeywordAnalyzer
{
    /**
     * Analyze keyword density and prominence
     */
    public function analyze(CrawlResult $crawlResult, array $keywords = []): array
    {
        if (!$crawlResult->isSuccessful() || empty($crawlResult->getContent())) {
            return $this->getEmptyResult();
        }

        $content = $crawlResult->getContent();
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new DOMXPath($dom);

        // Extract text content
        $bodyText = $this->extractBodyText($xpath);
        $headingText = $this->extractHeadingText($xpath);
        $firstParagraph = $this->extractFirstParagraph($xpath);

        $results = [
            'keyword_densities' => [],
            'keyword_prominence' => [],
            'auto_detected_keywords' => [],
            'keyword_stuffing_risk' => false,
            'long_tail_keywords' => [],
            'semantic_keywords' => [],
        ];

        // Analyze provided keywords
        foreach ($keywords as $keyword) {
            $results['keyword_densities'][$keyword] = $this->calculateKeywordDensity($bodyText, $keyword);
            $results['keyword_prominence'][$keyword] = $this->analyzeKeywordProminence($keyword, $headingText, $firstParagraph, $bodyText);
        }

        // Auto-detect keywords if none provided
        if (empty($keywords)) {
            $results['auto_detected_keywords'] = $this->autoDetectKeywords($bodyText);
            
            // Analyze auto-detected keywords
            foreach ($results['auto_detected_keywords'] as $keyword => $frequency) {
                $results['keyword_densities'][$keyword] = $this->calculateKeywordDensity($bodyText, $keyword);
                $results['keyword_prominence'][$keyword] = $this->analyzeKeywordProminence($keyword, $headingText, $firstParagraph, $bodyText);
            }
        }

        // Check for keyword stuffing
        $results['keyword_stuffing_risk'] = $this->detectKeywordStuffing($results['keyword_densities']);

        // Identify long-tail keywords
        $results['long_tail_keywords'] = $this->identifyLongTailKeywords($bodyText);

        // Find semantic keywords
        $results['semantic_keywords'] = $this->findSemanticKeywords($bodyText, $keywords);

        return $results;
    }

    /**
     * Extract body text content
     */
    private function extractBodyText(DOMXPath $xpath): string
    {
        $bodyNodes = $xpath->query('//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::noscript)]');
        $text = '';
        
        foreach ($bodyNodes as $node) {
            $text .= ' ' . $node->textContent;
        }
        
        return TextProcessor::cleanText($text);
    }

    /**
     * Extract heading text (H1-H6)
     */
    private function extractHeadingText(DOMXPath $xpath): string
    {
        $headingNodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        $text = '';
        
        foreach ($headingNodes as $node) {
            $text .= ' ' . $node->textContent;
        }
        
        return TextProcessor::cleanText($text);
    }

    /**
     * Extract first paragraph text
     */
    private function extractFirstParagraph(DOMXPath $xpath): string
    {
        $firstP = $xpath->query('//body//p[1]')->item(0);
        
        if ($firstP) {
            return TextProcessor::cleanText($firstP->textContent);
        }
        
        return '';
    }

    /**
     * Calculate keyword density
     */
    private function calculateKeywordDensity(string $text, string $keyword): float
    {
        return TextProcessor::calculateKeywordDensity($text, $keyword);
    }

    /**
     * Analyze keyword prominence in important page sections
     */
    private function analyzeKeywordProminence(string $keyword, string $headings, string $firstParagraph, string $bodyText): array
    {
        $keywordLower = mb_strtolower($keyword);
        
        return [
            'in_headings' => mb_strpos(mb_strtolower($headings), $keywordLower) !== false,
            'in_first_paragraph' => mb_strpos(mb_strtolower($firstParagraph), $keywordLower) !== false,
            'heading_density' => TextProcessor::calculateKeywordDensity($headings, $keyword),
            'first_paragraph_density' => TextProcessor::calculateKeywordDensity($firstParagraph, $keyword),
            'body_density' => TextProcessor::calculateKeywordDensity($bodyText, $keyword),
            'prominence_score' => $this->calculateProminenceScore($keyword, $headings, $firstParagraph, $bodyText),
        ];
    }

    /**
     * Calculate prominence score (0-100)
     */
    private function calculateProminenceScore(string $keyword, string $headings, string $firstParagraph, string $bodyText): float
    {
        $score = 0;
        $keywordLower = mb_strtolower($keyword);
        
        // Heading presence (40 points)
        if (mb_strpos(mb_strtolower($headings), $keywordLower) !== false) {
            $score += 40;
        }
        
        // First paragraph presence (30 points)
        if (mb_strpos(mb_strtolower($firstParagraph), $keywordLower) !== false) {
            $score += 30;
        }
        
        // Body density (30 points based on ideal density 1-3%)
        $bodyDensity = TextProcessor::calculateKeywordDensity($bodyText, $keyword);
        if ($bodyDensity >= 1 && $bodyDensity <= 3) {
            $score += 30;
        } elseif ($bodyDensity > 0) {
            // Partial score for non-ideal density
            $score += min(30, $bodyDensity * 10);
        }
        
        return min(100, $score);
    }

    /**
     * Auto-detect important keywords from content
     */
    private function autoDetectKeywords(string $text): array
    {
        $commonWords = TextProcessor::getMostCommonWords($text, 20);
        $keywords = [];
        
        foreach ($commonWords as $word => $frequency) {
            $density = TextProcessor::calculateKeywordDensity($text, $word);
            
            // Only include words with reasonable density and length
            if ($density >= 0.5 && $density <= 5 && mb_strlen($word) >= 3) {
                $keywords[$word] = $frequency;
            }
        }
        
        return $keywords;
    }

    /**
     * Detect potential keyword stuffing
     */
    private function detectKeywordStuffing(array $keywordDensities): bool
    {
        foreach ($keywordDensities as $keyword => $density) {
            // Flag as stuffing if density > 5% or if multiple keywords > 3%
            if ($density > 5) {
                return true;
            }
        }
        
        $highDensityCount = count(array_filter($keywordDensities, function($density) {
            return $density > 3;
        }));
        
        return $highDensityCount > 3;
    }

    /**
     * Identify long-tail keywords (3+ word phrases)
     */
    private function identifyLongTailKeywords(string $text): array
    {
        $sentences = TextProcessor::getSentences($text);
        $longTailKeywords = [];
        
        foreach ($sentences as $sentence) {
            $words = TextProcessor::getWords($sentence);
            
            // Extract 3-5 word phrases
            for ($i = 0; $i <= count($words) - 3; $i++) {
                for ($length = 3; $length <= 5 && $i + $length <= count($words); $length++) {
                    $phrase = implode(' ', array_slice($words, $i, $length));
                    
                    if (mb_strlen($phrase) >= 10) { // Minimum phrase length
                        $density = TextProcessor::calculateKeywordDensity($text, $phrase);
                        
                        if ($density >= 0.1 && $density <= 2) {
                            $longTailKeywords[$phrase] = $density;
                        }
                    }
                }
            }
        }
        
        // Sort by density and return top 10
        arsort($longTailKeywords);
        return array_slice($longTailKeywords, 0, 10, true);
    }

    /**
     * Find semantic keywords related to main keywords
     */
    private function findSemanticKeywords(string $text, array $mainKeywords): array
    {
        $words = TextProcessor::getMostCommonWords($text, 50);
        $semanticKeywords = [];
        
        foreach ($mainKeywords as $mainKeyword) {
            $mainWords = explode(' ', mb_strtolower($mainKeyword));
            
            foreach ($words as $word => $frequency) {
                // Skip if it's part of the main keyword
                if (in_array($word, $mainWords)) {
                    continue;
                }
                
                // Simple semantic relationship detection
                // (In a real implementation, you might use NLP libraries or APIs)
                if ($this->isSemanticallySimilar($mainKeyword, $word)) {
                    $semanticKeywords[$word] = TextProcessor::calculateKeywordDensity($text, $word);
                }
            }
        }
        
        // Sort by density and return top 10
        arsort($semanticKeywords);
        return array_slice($semanticKeywords, 0, 10, true);
    }

    /**
     * Basic semantic similarity check
     */
    private function isSemanticallySimilar(string $mainKeyword, string $word): bool
    {
        // This is a very basic implementation
        // In practice, you'd use more sophisticated NLP techniques
        
        $mainWords = explode(' ', mb_strtolower($mainKeyword));
        $wordLower = mb_strtolower($word);
        
        // Check for common word roots or stems
        foreach ($mainWords as $mainWord) {
            if (mb_strlen($mainWord) >= 4 && mb_strlen($wordLower) >= 4) {
                $mainRoot = mb_substr($mainWord, 0, -2);
                $wordRoot = mb_substr($wordLower, 0, -2);
                
                if ($mainRoot === $wordRoot) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get empty result structure
     */
    private function getEmptyResult(): array
    {
        return [
            'keyword_densities' => [],
            'keyword_prominence' => [],
            'auto_detected_keywords' => [],
            'keyword_stuffing_risk' => false,
            'long_tail_keywords' => [],
            'semantic_keywords' => [],
        ];
    }
}