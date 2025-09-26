<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Image optimization analysis service
 * Analyzes images for SEO and accessibility compliance including
 * alt text quality, file size optimization, format recommendations,
 * and accessibility best practices
 */
class ImageOptimizationAnalyzer
{
    private array $optimizedFormats = ['webp', 'avif', 'jpg', 'jpeg', 'png'];
    private array $unoptimizedFormats = ['bmp', 'tiff', 'gif'];
    private int $maxRecommendedSize = 100 * 1024; // 100KB
    private int $maxAcceptableSize = 500 * 1024; // 500KB

    /**
     * Analyze image optimization and accessibility
     */
    public function analyze(array $imagesData, string $html = '', string $url = '', array $options = []): array
    {
        Log::debug('Starting image optimization analysis', [
            'total_images' => $imagesData['total_count'] ?? 0,
            'url' => $url
        ]);

        $startTime = microtime(true);

        try {
            $images = $imagesData['images'] ?? [];

            if (empty($images)) {
                return $this->generateEmptyImageAnalysis();
            }

            // Analyze alt text quality
            $altTextAnalysis = $this->analyzeAltText($images);

            // Analyze image formats and optimization
            $formatAnalysis = $this->analyzeImageFormats($images);

            // Analyze image sizes (if accessible)
            $sizeAnalysis = $this->analyzeImageSizes($images, $url, $options);

            // Check accessibility compliance
            $accessibilityAnalysis = $this->analyzeAccessibilityCompliance($images, $html);

            // Analyze SEO optimization
            $seoAnalysis = $this->analyzeSeoOptimization($images, $html);

            // Check responsive image implementation
            $responsiveAnalysis = $this->analyzeResponsiveImplementation($html);

            // Performance impact analysis
            $performanceAnalysis = $this->analyzePerformanceImpact($images, $sizeAnalysis);

            // Calculate overall image optimization score
            $overallScore = $this->calculateImageOptimizationScore([
                'alt_text' => $altTextAnalysis,
                'format' => $formatAnalysis,
                'size' => $sizeAnalysis,
                'accessibility' => $accessibilityAnalysis,
                'seo' => $seoAnalysis,
                'responsive' => $responsiveAnalysis,
                'performance' => $performanceAnalysis
            ]);

            // Generate recommendations
            $recommendations = $this->generateImageRecommendations([
                'alt_text' => $altTextAnalysis,
                'format' => $formatAnalysis,
                'size' => $sizeAnalysis,
                'accessibility' => $accessibilityAnalysis,
                'seo' => $seoAnalysis,
                'responsive' => $responsiveAnalysis,
                'performance' => $performanceAnalysis,
                'overall_score' => $overallScore
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'total_images' => count($images),
                'image_summary' => $this->generateImageSummary($images, $imagesData),
                'alt_text_analysis' => $altTextAnalysis,
                'format_analysis' => $formatAnalysis,
                'size_analysis' => $sizeAnalysis,
                'accessibility_analysis' => $accessibilityAnalysis,
                'seo_analysis' => $seoAnalysis,
                'responsive_analysis' => $responsiveAnalysis,
                'performance_analysis' => $performanceAnalysis,
                'recommendations' => $recommendations,
                'optimization_insights' => $this->generateOptimizationInsights($overallScore, $images, $altTextAnalysis)
            ];

            Log::info('Image optimization analysis completed', [
                'total_images' => count($images),
                'overall_score' => $overallScore,
                'images_without_alt' => $altTextAnalysis['without_alt_count'],
                'optimization_issues' => count($recommendations)
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Image optimization analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Analyze alt text quality and accessibility
     */
    private function analyzeAltText(array $images): array
    {
        $score = 100;
        $issues = [];
        $withoutAlt = 0;
        $withEmptyAlt = 0;
        $withGoodAlt = 0;
        $withPoorAlt = 0;

        $altTextAnalysis = [];

        foreach ($images as $image) {
            $altText = $image['alt'] ?? '';
            $src = $image['src'] ?? '';

            $analysis = [
                'src' => $src,
                'alt_text' => $altText,
                'has_alt' => !empty($altText),
                'alt_length' => strlen($altText),
                'is_decorative' => $image['is_decorative'] ?? false,
                'quality_score' => 0,
                'quality_level' => 'Poor',
                'issues' => []
            ];

            if (empty($altText)) {
                if (!($image['is_decorative'] ?? false)) {
                    $withoutAlt++;
                    $analysis['issues'][] = 'Missing alt text';
                    $score -= 10;
                } else {
                    $analysis['quality_score'] = 100;
                    $analysis['quality_level'] = 'Excellent (Decorative)';
                }
            } else {
                $altQuality = $this->evaluateAltTextQuality($altText, $src);
                $analysis['quality_score'] = $altQuality['score'];
                $analysis['quality_level'] = $altQuality['level'];
                $analysis['issues'] = $altQuality['issues'];

                if ($altQuality['score'] >= 80) {
                    $withGoodAlt++;
                } elseif ($altQuality['score'] >= 40) {
                    $withPoorAlt++;
                    $score -= 5;
                } else {
                    $withEmptyAlt++;
                    $score -= 8;
                }
            }

            $altTextAnalysis[] = $analysis;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'total_images' => count($images),
            'without_alt_count' => $withoutAlt,
            'with_empty_alt_count' => $withEmptyAlt,
            'with_good_alt_count' => $withGoodAlt,
            'with_poor_alt_count' => $withPoorAlt,
            'alt_text_coverage' => count($images) > 0 ? round((count($images) - $withoutAlt) / count($images) * 100, 1) : 0,
            'average_alt_length' => $this->calculateAverageAltLength($altTextAnalysis),
            'detailed_analysis' => $altTextAnalysis,
            'compliance_level' => $this->getAltTextComplianceLevel($score)
        ];
    }

    /**
     * Analyze image formats and optimization opportunities
     */
    private function analyzeImageFormats(array $images): array
    {
        $score = 100;
        $formatCounts = [];
        $optimizationOpportunities = [];

        foreach ($images as $image) {
            $src = $image['src'] ?? '';
            $format = $this->extractImageFormat($src);

            $formatCounts[$format] = ($formatCounts[$format] ?? 0) + 1;

            $formatAnalysis = [
                'src' => $src,
                'format' => $format,
                'is_optimized' => in_array($format, $this->optimizedFormats),
                'is_modern' => in_array($format, ['webp', 'avif']),
                'optimization_potential' => $this->getOptimizationPotential($format)
            ];

            if (!$formatAnalysis['is_optimized']) {
                $optimizationOpportunities[] = $formatAnalysis;
                $score -= 15;
            } elseif (!$formatAnalysis['is_modern'] && in_array($format, ['jpg', 'jpeg', 'png'])) {
                $optimizationOpportunities[] = $formatAnalysis;
                $score -= 5;
            }
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'format_distribution' => $formatCounts,
            'optimization_opportunities' => $optimizationOpportunities,
            'modern_format_usage' => $this->calculateModernFormatUsage($formatCounts),
            'format_recommendations' => $this->generateFormatRecommendations($formatCounts),
            'total_images' => count($images)
        ];
    }

    /**
     * Analyze image sizes and loading performance
     */
    private function analyzeImageSizes(array $images, string $url, array $options): array
    {
        $score = 100;
        $sizeAnalysis = [];
        $totalEstimatedSize = 0;
        $largeImages = 0;
        $oversizedImages = 0;

        $checkSizes = $options['check_image_sizes'] ?? false;

        foreach ($images as $image) {
            $src = $image['src'] ?? '';
            $width = $image['width'] ?? null;
            $height = $image['height'] ?? null;

            $analysis = [
                'src' => $src,
                'width' => $width,
                'height' => $height,
                'has_dimensions' => !empty($width) && !empty($height),
                'estimated_size' => null,
                'actual_size' => null,
                'size_category' => 'Unknown',
                'optimization_needed' => false
            ];

            if ($checkSizes && !empty($src)) {
                $sizeInfo = $this->checkImageSize($src);
                $analysis['actual_size'] = $sizeInfo['size'];
                $analysis['size_category'] = $sizeInfo['category'];
                $analysis['optimization_needed'] = $sizeInfo['needs_optimization'];

                if ($sizeInfo['size'] > $this->maxAcceptableSize) {
                    $oversizedImages++;
                    $score -= 15;
                } elseif ($sizeInfo['size'] > $this->maxRecommendedSize) {
                    $largeImages++;
                    $score -= 5;
                }

                $totalEstimatedSize += $sizeInfo['size'];
            } else {
                // Estimate size based on format and dimensions
                $estimatedSize = $this->estimateImageSize($src, $width, $height);
                $analysis['estimated_size'] = $estimatedSize;
                $totalEstimatedSize += $estimatedSize;

                if ($estimatedSize > $this->maxRecommendedSize) {
                    $analysis['optimization_needed'] = true;
                    $score -= 3;
                }
            }

            $sizeAnalysis[] = $analysis;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'total_estimated_size' => $totalEstimatedSize,
            'large_images_count' => $largeImages,
            'oversized_images_count' => $oversizedImages,
            'average_image_size' => count($images) > 0 ? round($totalEstimatedSize / count($images)) : 0,
            'size_analysis' => $sizeAnalysis,
            'size_recommendations' => $this->generateSizeRecommendations($sizeAnalysis),
            'performance_impact' => $this->calculatePerformanceImpact($totalEstimatedSize, count($images))
        ];
    }

    /**
     * Analyze accessibility compliance for images
     */
    private function analyzeAccessibilityCompliance(array $images, string $html): array
    {
        $score = 100;
        $issues = [];
        $complianceChecks = [];

        foreach ($images as $image) {
            $altText = $image['alt'] ?? '';
            $src = $image['src'] ?? '';

            $checks = [
                'has_alt_attribute' => !empty($altText) || ($image['is_decorative'] ?? false),
                'alt_not_redundant' => !$this->isRedundantAltText($altText, $src),
                'alt_not_too_long' => strlen($altText) <= 125,
                'not_text_in_image' => !$this->appearsToBeTextImage($altText),
                'proper_decorative_handling' => $this->isProperlyMarkedDecorative($image)
            ];

            $complianceScore = (array_sum($checks) / count($checks)) * 100;

            $complianceChecks[] = [
                'src' => $src,
                'compliance_score' => round($complianceScore, 1),
                'checks' => $checks,
                'issues' => $this->identifyAccessibilityIssues($checks, $altText, $src)
            ];

            if ($complianceScore < 60) {
                $score -= 10;
            } elseif ($complianceScore < 80) {
                $score -= 5;
            }
        }

        // Check for image-heavy content without proper structure
        $imageToTextRatio = $this->calculateImageToTextRatio($images, $html);
        if ($imageToTextRatio > 0.5) { // More than 50% images
            $issues[] = 'High image-to-text ratio may affect accessibility';
            $score -= 10;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'compliance_level' => $this->getAccessibilityComplianceLevel($score),
            'issues' => $issues,
            'compliance_checks' => $complianceChecks,
            'image_to_text_ratio' => $imageToTextRatio,
            'wcag_compliance' => $this->determineWCAGCompliance($score, $complianceChecks)
        ];
    }

    /**
     * Analyze SEO optimization for images
     */
    private function analyzeSeoOptimization(array $images, string $html): array
    {
        $score = 100;
        $seoIssues = [];
        $seoAnalysis = [];

        foreach ($images as $image) {
            $src = $image['src'] ?? '';
            $altText = $image['alt'] ?? '';
            $title = $image['title'] ?? '';

            $analysis = [
                'src' => $src,
                'filename_seo_friendly' => $this->isSeoFriendlyFilename($src),
                'has_descriptive_alt' => !empty($altText) && strlen($altText) > 5,
                'has_title_attribute' => !empty($title),
                'alt_contains_keywords' => $this->containsLikelyKeywords($altText),
                'appropriate_context' => $this->hasAppropriateContext($image, $html)
            ];

            $seoScore = (array_sum($analysis) / (count($analysis) - 1)) * 100; // Exclude src from scoring
            $analysis['seo_score'] = round($seoScore, 1);

            if ($seoScore < 60) {
                $score -= 8;
            } elseif ($seoScore < 80) {
                $score -= 3;
            }

            $seoAnalysis[] = $analysis;
        }

        // Check for missing important images (like logos, product images)
        $missingImportantImages = $this->checkForMissingImportantImages($html);
        if (!empty($missingImportantImages)) {
            $seoIssues = array_merge($seoIssues, $missingImportantImages);
            $score -= count($missingImportantImages) * 5;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'seo_optimized' => $score >= 70,
            'issues' => $seoIssues,
            'seo_analysis' => $seoAnalysis,
            'optimization_opportunities' => $this->identifySeoOpportunities($seoAnalysis),
            'keyword_optimization' => $this->analyzeSeoKeywordUsage($images)
        ];
    }

    /**
     * Analyze responsive image implementation
     */
    private function analyzeResponsiveImplementation(string $html): array
    {
        $score = 0;
        $features = [];

        // Check for picture elements
        $pictureCount = substr_count($html, '<picture');
        if ($pictureCount > 0) {
            $features['picture_elements'] = $pictureCount;
            $score += 30;
        }

        // Check for srcset usage
        $srcsetCount = substr_count($html, 'srcset=');
        if ($srcsetCount > 0) {
            $features['srcset_usage'] = $srcsetCount;
            $score += 40;
        }

        // Check for sizes attribute
        $sizesCount = substr_count($html, 'sizes=');
        if ($sizesCount > 0) {
            $features['sizes_attribute'] = $sizesCount;
            $score += 20;
        }

        // Check for lazy loading
        $lazyLoadingCount = substr_count($html, 'loading="lazy"');
        if ($lazyLoadingCount > 0) {
            $features['lazy_loading'] = $lazyLoadingCount;
            $score += 10;
        }

        return [
            'score' => min(100, $score),
            'is_responsive' => $score >= 50,
            'features' => $features,
            'implementation_level' => $this->getResponsiveImplementationLevel($score),
            'recommendations' => $this->generateResponsiveRecommendations($features, $score)
        ];
    }

    /**
     * Analyze performance impact of images
     */
    private function analyzePerformanceImpact(array $images, array $sizeAnalysis): array
    {
        $totalSize = $sizeAnalysis['total_estimated_size'] ?? 0;
        $imageCount = count($images);

        $performanceScore = 100;

        // Penalty for large total size
        if ($totalSize > 2000000) { // 2MB
            $performanceScore -= 30;
        } elseif ($totalSize > 1000000) { // 1MB
            $performanceScore -= 15;
        } elseif ($totalSize > 500000) { // 500KB
            $performanceScore -= 5;
        }

        // Penalty for too many images
        if ($imageCount > 20) {
            $performanceScore -= 20;
        } elseif ($imageCount > 10) {
            $performanceScore -= 10;
        }

        $loadingTime = $this->estimateLoadingTime($totalSize);

        return [
            'score' => max(0, min(100, round($performanceScore, 1))),
            'total_size' => $totalSize,
            'image_count' => $imageCount,
            'estimated_loading_time' => $loadingTime,
            'performance_impact' => $this->getPerformanceImpactLevel($performanceScore),
            'optimization_potential' => $this->calculateOptimizationPotential($sizeAnalysis)
        ];
    }

    /**
     * Calculate overall image optimization score
     */
    private function calculateImageOptimizationScore(array $analyses): array
    {
        $weights = [
            'alt_text' => 0.25,        // 25%
            'accessibility' => 0.20,   // 20%
            'seo' => 0.20,            // 20%
            'format' => 0.15,         // 15%
            'performance' => 0.10,     // 10%
            'size' => 0.10            // 10%
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
            'grade' => $this->getOptimizationGrade($totalScore)
        ];
    }

    // Helper methods

    private function generateEmptyImageAnalysis(): array
    {
        return [
            'analyzed_at' => date('c'),
            'analysis_duration_ms' => 0,
            'overall_score' => ['overall' => 0, 'grade' => 'N/A'],
            'total_images' => 0,
            'image_summary' => ['no_images' => true],
            'recommendations' => [[
                'type' => 'suggestion',
                'category' => 'content',
                'message' => 'No images found on the page',
                'impact' => 'low',
                'fix' => 'Consider adding relevant images to enhance user experience and SEO'
            ]],
            'optimization_insights' => ['No images found to analyze']
        ];
    }

    private function evaluateAltTextQuality(string $altText, string $src): array
    {
        $score = 100;
        $issues = [];

        if (empty($altText)) {
            return ['score' => 0, 'level' => 'None', 'issues' => ['Missing alt text']];
        }

        // Check length
        $length = strlen($altText);
        if ($length < 3) {
            $score -= 40;
            $issues[] = 'Alt text too short';
        } elseif ($length > 125) {
            $score -= 20;
            $issues[] = 'Alt text too long (over 125 characters)';
        }

        // Check for common bad practices
        if (preg_match('/^(image|picture|photo|graphic|logo|icon)\s*(of|for)?\s*/i', $altText)) {
            $score -= 30;
            $issues[] = 'Alt text starts with redundant words (image, picture, etc.)';
        }

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $altText)) {
            $score -= 25;
            $issues[] = 'Alt text contains file extension';
        }

        // Check if alt text is just the filename
        $filename = pathinfo($src, PATHINFO_FILENAME);
        if (strtolower($altText) === strtolower($filename)) {
            $score -= 40;
            $issues[] = 'Alt text is just the filename';
        }

        // Check for descriptiveness
        $wordCount = str_word_count($altText);
        if ($wordCount < 2) {
            $score -= 20;
            $issues[] = 'Alt text not descriptive enough';
        } elseif ($wordCount >= 3) {
            $score += 10; // Bonus for descriptive text
        }

        // Check for context and specificity
        if (preg_match('/\b(specific|detailed|clear)\b/i', $altText)) {
            $score += 5;
        }

        $level = $this->getAltTextQualityLevel($score);

        return [
            'score' => max(0, min(100, round($score, 1))),
            'level' => $level,
            'issues' => $issues
        ];
    }

    private function extractImageFormat(string $src): string
    {
        $extension = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // Handle common variations
        $formatMap = [
            'jpg' => 'jpeg',
            'jpeg' => 'jpeg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            'avif' => 'avif',
            'svg' => 'svg',
            'bmp' => 'bmp',
            'tiff' => 'tiff',
            'tif' => 'tiff'
        ];

        return $formatMap[$extension] ?? 'unknown';
    }

    private function getOptimizationPotential(string $format): string
    {
        if (in_array($format, ['webp', 'avif'])) {
            return 'Excellent';
        } elseif (in_array($format, ['jpg', 'jpeg', 'png'])) {
            return 'Good - consider modern formats';
        } elseif (in_array($format, ['gif'])) {
            return 'Fair - consider alternatives';
        } else {
            return 'Poor - needs optimization';
        }
    }

    private function calculateModernFormatUsage(array $formatCounts): array
    {
        $modernFormats = ['webp', 'avif'];
        $modernCount = 0;
        $totalCount = array_sum($formatCounts);

        foreach ($modernFormats as $format) {
            $modernCount += $formatCounts[$format] ?? 0;
        }

        return [
            'modern_count' => $modernCount,
            'total_count' => $totalCount,
            'percentage' => $totalCount > 0 ? round(($modernCount / $totalCount) * 100, 1) : 0
        ];
    }

    private function generateFormatRecommendations(array $formatCounts): array
    {
        $recommendations = [];

        foreach ($formatCounts as $format => $count) {
            if (in_array($format, $this->unoptimizedFormats)) {
                $recommendations[] = "Convert {$count} {$format} image(s) to optimized formats (WebP, AVIF, or JPEG)";
            } elseif (in_array($format, ['jpg', 'jpeg', 'png']) && $count > 0) {
                $recommendations[] = "Consider converting {$count} {$format} image(s) to modern formats (WebP, AVIF) for better compression";
            }
        }

        return $recommendations;
    }

    private function checkImageSize(string $src): array
    {
        try {
            // In a real implementation, you would make HTTP requests to check file sizes
            // For this example, we'll estimate based on URL patterns

            $estimatedSize = $this->estimateImageSize($src);

            return [
                'size' => $estimatedSize,
                'category' => $this->getSizeCategory($estimatedSize),
                'needs_optimization' => $estimatedSize > $this->maxRecommendedSize
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to check image size', ['src' => $src, 'error' => $e->getMessage()]);

            return [
                'size' => 0,
                'category' => 'Unknown',
                'needs_optimization' => false
            ];
        }
    }

    private function estimateImageSize(string $src, $width = null, $height = null): int
    {
        $format = $this->extractImageFormat($src);

        // Base size estimation
        $baseSize = 50000; // 50KB base

        // Adjust based on format
        $formatMultiplier = match($format) {
            'webp' => 0.7,
            'avif' => 0.5,
            'jpeg', 'jpg' => 1.0,
            'png' => 1.5,
            'gif' => 0.8,
            'bmp' => 3.0,
            'tiff' => 4.0,
            default => 1.0
        };

        // Adjust based on dimensions if available
        if ($width && $height) {
            $pixels = (int)$width * (int)$height;
            $sizeMultiplier = sqrt($pixels / 250000); // 500x500 as baseline
            $baseSize *= $sizeMultiplier;
        }

        return (int)($baseSize * $formatMultiplier);
    }

    private function getSizeCategory(int $size): string
    {
        if ($size <= 30000) return 'Small';
        if ($size <= $this->maxRecommendedSize) return 'Medium';
        if ($size <= $this->maxAcceptableSize) return 'Large';
        return 'Very Large';
    }

    private function generateSizeRecommendations(array $sizeAnalysis): array
    {
        $recommendations = [];

        foreach ($sizeAnalysis as $analysis) {
            if ($analysis['optimization_needed']) {
                $size = $analysis['actual_size'] ?? $analysis['estimated_size'];
                $recommendations[] = "Optimize {$analysis['src']} (current: " . round($size/1024) . "KB)";
            }
        }

        return $recommendations;
    }

    private function calculatePerformanceImpact(int $totalSize, int $imageCount): string
    {
        if ($totalSize > 2000000) return 'High Impact';
        if ($totalSize > 1000000) return 'Medium Impact';
        if ($imageCount > 15) return 'Medium Impact';
        return 'Low Impact';
    }

    private function calculateAverageAltLength(array $altTextAnalysis): float
    {
        if (empty($altTextAnalysis)) return 0;

        $totalLength = array_sum(array_column($altTextAnalysis, 'alt_length'));
        return round($totalLength / count($altTextAnalysis), 1);
    }

    private function getAltTextComplianceLevel(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Very Poor';
    }

    private function getAltTextQualityLevel(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        if ($score >= 20) return 'Poor';
        return 'Very Poor';
    }

    private function isRedundantAltText(string $altText, string $src): bool
    {
        $filename = pathinfo($src, PATHINFO_FILENAME);
        return strtolower($altText) === strtolower($filename);
    }

    private function appearsToBeTextImage(string $altText): bool
    {
        // Simple heuristic to detect if image might contain text
        $textIndicators = ['button', 'sign', 'text', 'word', 'letter', 'font'];
        $text = strtolower($altText);

        foreach ($textIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                return true;
            }
        }

        return false;
    }

    private function isProperlyMarkedDecorative(array $image): bool
    {
        return ($image['is_decorative'] ?? false) && empty($image['alt']);
    }

    private function identifyAccessibilityIssues(array $checks, string $altText, string $src): array
    {
        $issues = [];

        if (!$checks['has_alt_attribute']) {
            $issues[] = 'Missing alt attribute';
        }
        if (!$checks['alt_not_redundant']) {
            $issues[] = 'Alt text is redundant with filename';
        }
        if (!$checks['alt_not_too_long']) {
            $issues[] = 'Alt text exceeds 125 characters';
        }
        if (!$checks['not_text_in_image']) {
            $issues[] = 'Appears to be text in image';
        }

        return $issues;
    }

    private function calculateImageToTextRatio(array $images, string $html): float
    {
        $imageCount = count($images);
        $textLength = strlen(strip_tags($html));

        if ($textLength === 0) return 1.0;

        // Rough estimation: assume average image represents ~100 characters worth of content
        $imageContentEquivalent = $imageCount * 100;

        return $imageContentEquivalent / ($textLength + $imageContentEquivalent);
    }

    private function getAccessibilityComplianceLevel(float $score): string
    {
        if ($score >= 85) return 'WCAG AA Compliant';
        if ($score >= 70) return 'Mostly Compliant';
        if ($score >= 50) return 'Partially Compliant';
        return 'Non-Compliant';
    }

    private function determineWCAGCompliance(float $score, array $complianceChecks): string
    {
        $criticalFailures = 0;

        foreach ($complianceChecks as $check) {
            if ($check['compliance_score'] < 50) {
                $criticalFailures++;
            }
        }

        if ($criticalFailures === 0 && $score >= 85) {
            return 'Pass';
        } elseif ($criticalFailures <= 1 && $score >= 70) {
            return 'Conditional Pass';
        } else {
            return 'Fail';
        }
    }

    private function isSeoFriendlyFilename(string $src): bool
    {
        $filename = pathinfo($src, PATHINFO_FILENAME);

        // Check for SEO-friendly patterns
        return !preg_match('/^(img|image|photo|pic|dsc|_mg)\d*$/i', $filename) &&
               !preg_match('/^\d{8,}$/', $filename) && // Pure numbers
               strlen($filename) > 3;
    }

    private function containsLikelyKeywords(string $altText): bool
    {
        // Simple heuristic for keyword detection
        return str_word_count($altText) >= 3 &&
               !preg_match('/^(image|photo|picture)\s+of/i', $altText);
    }

    private function hasAppropriateContext(array $image, string $html): bool
    {
        // Check if image appears to be in appropriate context (simplified)
        $src = $image['src'] ?? '';
        $position = strpos($html, $src);

        if ($position === false) return false;

        // Check for nearby text content
        $contextStart = max(0, $position - 200);
        $contextEnd = min(strlen($html), $position + 200);
        $context = substr($html, $contextStart, $contextEnd - $contextStart);

        $textContent = strip_tags($context);
        return strlen(trim($textContent)) > 50; // At least 50 characters of nearby text
    }

    private function checkForMissingImportantImages(string $html): array
    {
        $missing = [];

        // Check for missing logo
        if (!preg_match('/logo/i', $html) && !preg_match('/<img[^>]*logo/i', $html)) {
            $missing[] = 'Consider adding a logo image for brand recognition';
        }

        return $missing;
    }

    private function identifySeoOpportunities(array $seoAnalysis): array
    {
        $opportunities = [];

        foreach ($seoAnalysis as $analysis) {
            if ($analysis['seo_score'] < 70) {
                $opportunities[] = [
                    'src' => $analysis['src'],
                    'issues' => $this->getSeoIssues($analysis),
                    'potential_improvement' => round(100 - $analysis['seo_score'], 1)
                ];
            }
        }

        return $opportunities;
    }

    private function getSeoIssues(array $analysis): array
    {
        $issues = [];

        if (!$analysis['filename_seo_friendly']) {
            $issues[] = 'Filename not SEO-friendly';
        }
        if (!$analysis['has_descriptive_alt']) {
            $issues[] = 'Alt text not descriptive enough';
        }
        if (!$analysis['alt_contains_keywords']) {
            $issues[] = 'Alt text lacks relevant keywords';
        }

        return $issues;
    }

    private function analyzeSeoKeywordUsage(array $images): array
    {
        $keywordAnalysis = [
            'images_with_keywords' => 0,
            'total_keywords_found' => 0,
            'keyword_density' => 0
        ];

        foreach ($images as $image) {
            $altText = $image['alt'] ?? '';
            if (!empty($altText)) {
                $wordCount = str_word_count($altText);
                if ($wordCount >= 3) {
                    $keywordAnalysis['images_with_keywords']++;
                    $keywordAnalysis['total_keywords_found'] += $wordCount;
                }
            }
        }

        if (count($images) > 0) {
            $keywordAnalysis['keyword_density'] = round(
                $keywordAnalysis['total_keywords_found'] / count($images), 1
            );
        }

        return $keywordAnalysis;
    }

    private function getResponsiveImplementationLevel(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        if ($score >= 20) return 'Poor';
        return 'None';
    }

    private function generateResponsiveRecommendations(array $features, float $score): array
    {
        $recommendations = [];

        if (!isset($features['srcset_usage'])) {
            $recommendations[] = 'Implement srcset for responsive images';
        }
        if (!isset($features['picture_elements'])) {
            $recommendations[] = 'Consider using picture elements for art direction';
        }
        if (!isset($features['lazy_loading'])) {
            $recommendations[] = 'Add lazy loading to improve performance';
        }

        return $recommendations;
    }

    private function estimateLoadingTime(int $totalSize): array
    {
        // Estimate loading times for different connection speeds
        $speeds = [
            'slow_3g' => 400, // 400 Kbps
            'fast_3g' => 1600, // 1.6 Mbps
            '4g' => 9000, // 9 Mbps
            'wifi' => 30000 // 30 Mbps
        ];

        $loadingTimes = [];

        foreach ($speeds as $connection => $speed) {
            $timeSeconds = ($totalSize * 8) / ($speed * 1000); // Convert to seconds
            $loadingTimes[$connection] = round($timeSeconds, 2);
        }

        return $loadingTimes;
    }

    private function getPerformanceImpactLevel(float $score): string
    {
        if ($score >= 80) return 'Low Impact';
        if ($score >= 60) return 'Medium Impact';
        if ($score >= 40) return 'High Impact';
        return 'Critical Impact';
    }

    private function calculateOptimizationPotential(array $sizeAnalysis): array
    {
        $optimizableImages = array_filter($sizeAnalysis['size_analysis'], fn($img) => $img['optimization_needed']);
        $potentialSavings = 0;

        foreach ($optimizableImages as $image) {
            $currentSize = $image['actual_size'] ?? $image['estimated_size'];
            $optimizedSize = $currentSize * 0.7; // Assume 30% savings
            $potentialSavings += $currentSize - $optimizedSize;
        }

        return [
            'optimizable_images' => count($optimizableImages),
            'potential_savings_bytes' => (int)$potentialSavings,
            'potential_savings_percentage' => $sizeAnalysis['total_estimated_size'] > 0 ?
                round(($potentialSavings / $sizeAnalysis['total_estimated_size']) * 100, 1) : 0
        ];
    }

    private function getOptimizationGrade(float $score): string
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

    private function generateImageSummary(array $images, array $imagesData): array
    {
        return [
            'total_images' => count($images),
            'with_alt_text' => count($images) - ($imagesData['without_alt_count'] ?? 0),
            'without_alt_text' => $imagesData['without_alt_count'] ?? 0,
            'decorative_images' => count(array_filter($images, fn($img) => $img['is_decorative'] ?? false)),
            'format_distribution' => $this->getFormatDistribution($images),
            'size_distribution' => $this->getSizeDistribution($images)
        ];
    }

    private function getFormatDistribution(array $images): array
    {
        $formats = [];
        foreach ($images as $image) {
            $format = $this->extractImageFormat($image['src'] ?? '');
            $formats[$format] = ($formats[$format] ?? 0) + 1;
        }
        return $formats;
    }

    private function getSizeDistribution(array $images): array
    {
        $distribution = ['small' => 0, 'medium' => 0, 'large' => 0, 'unknown' => 0];

        foreach ($images as $image) {
            $width = $image['width'] ?? null;
            $height = $image['height'] ?? null;

            if ($width && $height) {
                $pixels = (int)$width * (int)$height;
                if ($pixels < 250000) {
                    $distribution['small']++;
                } elseif ($pixels < 1000000) {
                    $distribution['medium']++;
                } else {
                    $distribution['large']++;
                }
            } else {
                $distribution['unknown']++;
            }
        }

        return $distribution;
    }

    private function generateImageRecommendations(array $analyses): array
    {
        $recommendations = [];

        // Alt text recommendations
        $altText = $analyses['alt_text'];
        if ($altText['without_alt_count'] > 0) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'accessibility',
                'message' => "Add alt text to {$altText['without_alt_count']} image(s)",
                'impact' => 'high',
                'fix' => 'Add descriptive alt attributes to all informational images'
            ];
        }

        if ($altText['with_poor_alt_count'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'accessibility',
                'message' => "Improve alt text quality for {$altText['with_poor_alt_count']} image(s)",
                'impact' => 'medium',
                'fix' => 'Write more descriptive, specific alt text that describes the image content and context'
            ];
        }

        // Format recommendations
        $format = $analyses['format'];
        if (!empty($format['optimization_opportunities'])) {
            $count = count($format['optimization_opportunities']);
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'performance',
                'message' => "Optimize format for {$count} image(s)",
                'impact' => 'medium',
                'fix' => 'Convert images to modern formats like WebP or AVIF for better compression'
            ];
        }

        // Size recommendations
        $size = $analyses['size'];
        if ($size['oversized_images_count'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'performance',
                'message' => "Reduce size of {$size['oversized_images_count']} oversized image(s)",
                'impact' => 'high',
                'fix' => 'Compress or resize images to reduce file size and improve loading speed'
            ];
        }

        // Responsive recommendations
        $responsive = $analyses['responsive'];
        if (!$responsive['is_responsive']) {
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'responsive',
                'message' => 'Implement responsive images',
                'impact' => 'medium',
                'fix' => 'Use srcset and sizes attributes to serve appropriate image sizes for different devices'
            ];
        }

        return $recommendations;
    }

    private function generateOptimizationInsights(array $overallScore, array $images, array $altTextAnalysis): array
    {
        $insights = [];

        if ($overallScore['overall'] >= 85) {
            $insights[] = 'Images are well-optimized for SEO and accessibility';
        } elseif ($overallScore['overall'] >= 70) {
            $insights[] = 'Images have good optimization with room for improvement';
        } else {
            $insights[] = 'Images need significant optimization for better performance and accessibility';
        }

        if ($altTextAnalysis['alt_text_coverage'] >= 90) {
            $insights[] = 'Excellent alt text coverage supports accessibility compliance';
        } elseif ($altTextAnalysis['alt_text_coverage'] < 50) {
            $insights[] = 'Poor alt text coverage creates significant accessibility barriers';
        }

        if (count($images) > 15) {
            $insights[] = 'High number of images may impact page loading performance';
        } elseif (count($images) === 0) {
            $insights[] = 'No images found - consider adding relevant visuals to enhance user experience';
        }

        return $insights;
    }
}