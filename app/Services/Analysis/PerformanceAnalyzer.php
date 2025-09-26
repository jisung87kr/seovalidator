<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Log;

/**
 * Page performance insights and optimization analysis
 * Analyzes technical performance aspects and provides actionable recommendations
 */
class PerformanceAnalyzer
{
    /**
     * Analyze page performance characteristics
     */
    public function analyze(string $html, string $url, array $domData = [], array $options = []): array
    {
        Log::debug('Starting performance analysis', [
            'url' => $url,
            'html_size' => strlen($html)
        ]);

        $startTime = microtime(true);

        try {
            // Extract performance data from DOM
            $performanceElements = $domData['performance_elements'] ?? [];
            $multimedia = $domData['multimedia'] ?? [];
            $security = $domData['security'] ?? [];

            // Analyze different performance aspects
            $resourceOptimization = $this->analyzeResourceOptimization($performanceElements, $html, $url);
            $imageOptimization = $this->analyzeImageOptimization($multimedia['images'] ?? [], $html);
            $scriptOptimization = $this->analyzeScriptOptimization($performanceElements, $html);
            $cssOptimization = $this->analyzeCssOptimization($html);
            $contentOptimization = $this->analyzeContentOptimization($html);
            $cacheOptimization = $this->analyzeCacheOptimization($html);
            $renderingOptimization = $this->analyzeRenderingOptimization($html, $performanceElements);

            // Calculate overall performance score
            $overallScore = $this->calculatePerformanceScore([
                'resource_optimization' => $resourceOptimization,
                'image_optimization' => $imageOptimization,
                'script_optimization' => $scriptOptimization,
                'css_optimization' => $cssOptimization,
                'content_optimization' => $contentOptimization,
                'cache_optimization' => $cacheOptimization,
                'rendering_optimization' => $renderingOptimization
            ]);

            // Generate performance recommendations
            $recommendations = $this->generatePerformanceRecommendations([
                'resource_optimization' => $resourceOptimization,
                'image_optimization' => $imageOptimization,
                'script_optimization' => $scriptOptimization,
                'css_optimization' => $cssOptimization,
                'content_optimization' => $contentOptimization,
                'cache_optimization' => $cacheOptimization,
                'rendering_optimization' => $renderingOptimization
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'resource_optimization' => $resourceOptimization,
                'image_optimization' => $imageOptimization,
                'script_optimization' => $scriptOptimization,
                'css_optimization' => $cssOptimization,
                'content_optimization' => $contentOptimization,
                'cache_optimization' => $cacheOptimization,
                'rendering_optimization' => $renderingOptimization,
                'recommendations' => $recommendations,
                'performance_budget' => $this->calculatePerformanceBudget($html, $performanceElements),
                'core_web_vitals_hints' => $this->getCoreWebVitalsHints($html, $performanceElements)
            ];

            Log::info('Performance analysis completed', [
                'url' => $url,
                'overall_score' => $overallScore,
                'recommendations_count' => count($recommendations)
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Performance analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Analyze resource optimization (preload, prefetch, etc.)
     */
    private function analyzeResourceOptimization(array $performanceElements, string $html, string $url): array
    {
        $preloadHints = $performanceElements['preload_hints'] ?? [];
        $resourceHints = $performanceElements['resource_hints'] ?? [];
        $criticalResources = $performanceElements['critical_resources'] ?? [];

        // Count different types of resource hints
        $preloadCount = $preloadHints['preload'] ?? 0;
        $prefetchCount = $preloadHints['prefetch'] ?? 0;
        $preconnectCount = $preloadHints['preconnect'] ?? 0;
        $dnsPrefetchCount = $preloadHints['dns_prefetch'] ?? 0;

        // Analyze critical CSS implementation
        $criticalCssCount = $criticalResources['critical_css'] ?? 0;
        $hasInlineCriticalCss = preg_match('/<style[^>]*>.*?<\/style>/is', $html) > 0;

        // Calculate score (0-100)
        $score = 0;
        if ($preloadCount > 0) $score += 15; // Preload usage
        if ($preconnectCount > 0) $score += 15; // Preconnect usage
        if ($dnsPrefetchCount > 0) $score += 10; // DNS prefetch usage
        if ($hasInlineCriticalCss || $criticalCssCount > 0) $score += 20; // Critical CSS
        if ($prefetchCount > 0) $score += 10; // Prefetch usage

        // Bonus for good resource hint implementation
        $totalHints = $preloadCount + $prefetchCount + $preconnectCount + $dnsPrefetchCount;
        if ($totalHints > 3) $score += 15;
        if ($totalHints > 5) $score += 15;

        return [
            'score' => min(100, $score),
            'preload_hints' => $preloadCount,
            'prefetch_hints' => $prefetchCount,
            'preconnect_hints' => $preconnectCount,
            'dns_prefetch_hints' => $dnsPrefetchCount,
            'critical_css_implemented' => $hasInlineCriticalCss || $criticalCssCount > 0,
            'total_resource_hints' => $totalHints,
            'issues' => $this->identifyResourceOptimizationIssues($preloadHints, $resourceHints, $hasInlineCriticalCss)
        ];
    }

    /**
     * Analyze image optimization
     */
    private function analyzeImageOptimization(array $images, string $html): array
    {
        // Count images and their optimization features
        preg_match_all('/<img[^>]*>/i', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);

        // Count lazy loading implementation
        $lazyImages = preg_match_all('/<img[^>]*loading=["\']lazy["\'][^>]*>/i', $html);

        // Count responsive images (srcset)
        $responsiveImages = preg_match_all('/<img[^>]*srcset=["\'][^"\']+["\'][^>]*>/i', $html);

        // Count WebP images
        $webpImages = preg_match_all('/<img[^>]*src=["\'][^"\']*\.webp["\'][^>]*>/i', $html);

        // Count images with width/height attributes
        $dimensionedImages = preg_match_all('/<img[^>]*width=["\'][^"\']+["\'][^>]*height=["\'][^"\']+["\'][^>]*>/i', $html);

        // Calculate score
        $score = 0;
        if ($totalImages > 0) {
            $lazyPercent = ($lazyImages / $totalImages) * 100;
            $responsivePercent = ($responsiveImages / $totalImages) * 100;
            $webpPercent = ($webpImages / $totalImages) * 100;
            $dimensionedPercent = ($dimensionedImages / $totalImages) * 100;

            $score += min(30, $lazyPercent * 0.3); // Up to 30 points for lazy loading
            $score += min(25, $responsivePercent * 0.25); // Up to 25 points for responsive images
            $score += min(20, $webpPercent * 0.2); // Up to 20 points for WebP usage
            $score += min(25, $dimensionedPercent * 0.25); // Up to 25 points for proper dimensions
        } else {
            $score = 100; // No images to optimize
        }

        return [
            'score' => round($score, 1),
            'total_images' => $totalImages,
            'lazy_loading_images' => $lazyImages,
            'responsive_images' => $responsiveImages,
            'webp_images' => $webpImages,
            'dimensioned_images' => $dimensionedImages,
            'lazy_loading_percentage' => $totalImages > 0 ? round(($lazyImages / $totalImages) * 100, 1) : 0,
            'responsive_percentage' => $totalImages > 0 ? round(($responsiveImages / $totalImages) * 100, 1) : 0,
            'modern_format_percentage' => $totalImages > 0 ? round(($webpImages / $totalImages) * 100, 1) : 0,
            'issues' => $this->identifyImageOptimizationIssues($totalImages, $lazyImages, $responsiveImages, $webpImages, $dimensionedImages)
        ];
    }

    /**
     * Analyze script optimization
     */
    private function analyzeScriptOptimization(array $performanceElements, string $html): array
    {
        // Count script tags and their optimization attributes
        preg_match_all('/<script[^>]*>/i', $html, $scriptMatches);
        $totalScripts = count($scriptMatches[0]);

        // Count async and defer scripts
        $asyncScripts = preg_match_all('/<script[^>]*async[^>]*>/i', $html);
        $deferScripts = preg_match_all('/<script[^>]*defer[^>]*>/i', $html);

        // Count inline scripts (potential render blocking)
        preg_match_all('/<script[^>]*>[\s\S]*?<\/script>/i', $html, $inlineMatches);
        $inlineScripts = 0;
        foreach ($inlineMatches[0] as $script) {
            if (!preg_match('/src=["\'][^"\']+["\']/', $script)) {
                $inlineScripts++;
            }
        }

        // Count third-party scripts
        $thirdPartyScripts = $performanceElements['third_party_scripts']['total_count'] ?? 0;
        $thirdPartyDomains = $performanceElements['third_party_scripts']['unique_domains'] ?? 0;

        // Calculate score
        $score = 0;
        if ($totalScripts > 0) {
            $optimizedScripts = $asyncScripts + $deferScripts;
            $optimizedPercent = ($optimizedScripts / $totalScripts) * 100;
            $score += min(40, $optimizedPercent * 0.4); // Up to 40 points for async/defer
        }

        // Penalties for performance issues
        $score += max(0, 30 - ($inlineScripts * 5)); // Penalty for inline scripts
        $score += max(0, 30 - ($thirdPartyDomains * 3)); // Penalty for too many third-party domains

        return [
            'score' => min(100, max(0, round($score, 1))),
            'total_scripts' => $totalScripts,
            'async_scripts' => $asyncScripts,
            'defer_scripts' => $deferScripts,
            'inline_scripts' => $inlineScripts,
            'third_party_scripts' => $thirdPartyScripts,
            'third_party_domains' => $thirdPartyDomains,
            'optimized_percentage' => $totalScripts > 0 ? round((($asyncScripts + $deferScripts) / $totalScripts) * 100, 1) : 0,
            'issues' => $this->identifyScriptOptimizationIssues($totalScripts, $asyncScripts, $deferScripts, $inlineScripts, $thirdPartyDomains)
        ];
    }

    /**
     * Analyze CSS optimization
     */
    private function analyzeCssOptimization(string $html): array
    {
        // Count CSS resources
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $html, $linkMatches);
        $externalCss = count($linkMatches[0]);

        // Count inline CSS
        preg_match_all('/<style[^>]*>.*?<\/style>/is', $html, $styleMatches);
        $inlineCss = count($styleMatches[0]);

        // Check for critical CSS optimization
        $hasCriticalCss = preg_match('/<link[^>]*media=["\']print["\'][^>]*onload/i', $html) > 0 ||
                          preg_match('/<style[^>]*>[\s\S]*?<\/style>/i', $html) > 0;

        // Check for CSS minification hints (no spaces after colons, etc.)
        $hasMinifiedCss = false;
        if (!empty($styleMatches[0])) {
            foreach ($styleMatches[0] as $style) {
                if (preg_match('/[a-z]:[a-z]/i', $style) && strlen($style) > 200) {
                    $hasMinifiedCss = true;
                    break;
                }
            }
        }

        // Calculate score
        $score = 0;

        // Bonus for reasonable CSS structure
        if ($externalCss <= 3) $score += 20; // Not too many CSS files
        if ($inlineCss > 0 && $inlineCss <= 2) $score += 15; // Some inline CSS for critical styles
        if ($hasCriticalCss) $score += 25; // Critical CSS implementation
        if ($hasMinifiedCss) $score += 15; // CSS appears to be minified

        // Additional points for optimization patterns
        if ($externalCss > 0 && $inlineCss > 0) $score += 25; // Good balance of external and inline

        return [
            'score' => min(100, $score),
            'external_css_files' => $externalCss,
            'inline_css_blocks' => $inlineCss,
            'critical_css_implemented' => $hasCriticalCss,
            'appears_minified' => $hasMinifiedCss,
            'total_css_resources' => $externalCss + $inlineCss,
            'issues' => $this->identifyCssOptimizationIssues($externalCss, $inlineCss, $hasCriticalCss)
        ];
    }

    /**
     * Analyze content optimization
     */
    private function analyzeContentOptimization(string $html): array
    {
        // Calculate HTML size
        $htmlSize = strlen($html);
        $htmlSizeKb = round($htmlSize / 1024, 1);

        // Count DOM elements
        $domElements = substr_count($html, '<');

        // Check for compression hints
        $isCompressed = $htmlSize > 1000 && strlen(gzcompress($html)) / $htmlSize < 0.8;

        // Check for excessive whitespace
        $cleanedHtml = preg_replace('/\s+/', ' ', $html);
        $whitespaceRatio = ($htmlSize - strlen($cleanedHtml)) / $htmlSize;

        // Calculate score
        $score = 0;

        // Size optimization (40 points)
        if ($htmlSizeKb < 50) $score += 40;
        elseif ($htmlSizeKb < 100) $score += 30;
        elseif ($htmlSizeKb < 200) $score += 20;
        elseif ($htmlSizeKb < 500) $score += 10;

        // DOM complexity (30 points)
        if ($domElements < 500) $score += 30;
        elseif ($domElements < 1000) $score += 20;
        elseif ($domElements < 1500) $score += 10;

        // Content efficiency (30 points)
        if ($whitespaceRatio < 0.1) $score += 15; // Minimal excessive whitespace
        if ($isCompressed) $score += 15; // Content appears compressible

        return [
            'score' => round($score, 1),
            'html_size_bytes' => $htmlSize,
            'html_size_kb' => $htmlSizeKb,
            'dom_elements' => $domElements,
            'appears_compressed' => $isCompressed,
            'whitespace_ratio' => round($whitespaceRatio, 3),
            'compression_potential' => $isCompressed ? 'Good' : 'High',
            'issues' => $this->identifyContentOptimizationIssues($htmlSizeKb, $domElements, $whitespaceRatio)
        ];
    }

    /**
     * Analyze cache optimization hints
     */
    private function analyzeCacheOptimization(string $html): array
    {
        // Look for cache-related meta tags and headers
        $hasEtag = preg_match('/<meta[^>]*http-equiv=["\']etag["\'][^>]*>/i', $html);
        $hasLastModified = preg_match('/<meta[^>]*http-equiv=["\']last-modified["\'][^>]*>/i', $html);
        $hasCacheControl = preg_match('/<meta[^>]*http-equiv=["\']cache-control["\'][^>]*>/i', $html);

        // Check for versioned assets
        $versionedAssets = preg_match_all('/\.(css|js)\?v=|\.(css|js)\?version=|\/v\d+\//i', $html);

        // Check for CDN usage (common CDN domains)
        $cdnPatterns = [
            'cloudflare.com', 'amazonaws.com', 'cloudfront.net', 'fastly.com',
            'maxcdn.com', 'jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com'
        ];

        $cdnUsage = 0;
        foreach ($cdnPatterns as $pattern) {
            if (stripos($html, $pattern) !== false) {
                $cdnUsage++;
            }
        }

        // Calculate score
        $score = 0;

        if ($hasEtag) $score += 15;
        if ($hasLastModified) $score += 15;
        if ($hasCacheControl) $score += 20;
        if ($versionedAssets > 0) $score += 25;
        if ($cdnUsage > 0) $score += 25;

        return [
            'score' => min(100, $score),
            'has_etag_meta' => (bool)$hasEtag,
            'has_last_modified_meta' => (bool)$hasLastModified,
            'has_cache_control_meta' => (bool)$hasCacheControl,
            'versioned_assets' => $versionedAssets,
            'cdn_usage_detected' => $cdnUsage > 0,
            'cdn_services_detected' => $cdnUsage,
            'issues' => $this->identifyCacheOptimizationIssues($hasEtag, $hasLastModified, $hasCacheControl, $versionedAssets, $cdnUsage)
        ];
    }

    /**
     * Analyze rendering optimization
     */
    private function analyzeRenderingOptimization(string $html, array $performanceElements): array
    {
        // Check for render-blocking resources
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $html, $cssMatches);
        preg_match_all('/<script[^>]*src=[^>]*(?!async|defer)[^>]*>/i', $html, $blockingScriptMatches);

        $renderBlockingCss = count($cssMatches[0]);
        $renderBlockingScripts = count($blockingScriptMatches[0]);

        // Check for above-the-fold optimization
        $hasPreloadCriticalCss = preg_match('/<link[^>]*rel=["\']preload["\'][^>]*as=["\']style["\'][^>]*>/i', $html);
        $hasCriticalInlineCss = preg_match('/<style[^>]*>[\s\S]{100,2000}<\/style>/i', $html); // Reasonable amount of inline CSS

        // Check for font optimization
        $hasFontDisplay = preg_match('/font-display\s*:\s*(swap|fallback|optional)/i', $html);
        $preloadsWebFonts = preg_match('/<link[^>]*rel=["\']preload["\'][^>]*as=["\']font["\'][^>]*>/i', $html);

        // Check for image optimization for CLS
        preg_match_all('/<img[^>]*width=["\'][^"\']+["\'][^>]*height=["\'][^"\']+["\'][^>]*>/i', $html, $dimensionedImgMatches);
        preg_match_all('/<img[^>]*>/i', $html, $allImgMatches);

        $totalImages = count($allImgMatches[0]);
        $dimensionedImages = count($dimensionedImgMatches[0]);

        // Calculate score
        $score = 0;

        // Penalty for render-blocking resources
        $score += max(0, 40 - ($renderBlockingCss * 5) - ($renderBlockingScripts * 8));

        // Bonus for optimization techniques
        if ($hasPreloadCriticalCss || $hasCriticalInlineCss) $score += 25;
        if ($hasFontDisplay || $preloadsWebFonts) $score += 20;
        if ($totalImages > 0 && ($dimensionedImages / $totalImages) > 0.8) $score += 15;

        return [
            'score' => min(100, max(0, round($score, 1))),
            'render_blocking_css' => $renderBlockingCss,
            'render_blocking_scripts' => $renderBlockingScripts,
            'critical_css_optimized' => $hasPreloadCriticalCss || $hasCriticalInlineCss,
            'font_rendering_optimized' => $hasFontDisplay || $preloadsWebFonts,
            'images_with_dimensions' => $dimensionedImages,
            'total_images' => $totalImages,
            'cls_prevention_score' => $totalImages > 0 ? round(($dimensionedImages / $totalImages) * 100, 1) : 100,
            'issues' => $this->identifyRenderingOptimizationIssues($renderBlockingCss, $renderBlockingScripts, $hasPreloadCriticalCss, $hasCriticalInlineCss, $dimensionedImages, $totalImages)
        ];
    }

    /**
     * Calculate overall performance score from all components
     */
    private function calculatePerformanceScore(array $components): float
    {
        $weights = [
            'resource_optimization' => 0.20,
            'image_optimization' => 0.20,
            'script_optimization' => 0.20,
            'css_optimization' => 0.15,
            'content_optimization' => 0.10,
            'cache_optimization' => 0.10,
            'rendering_optimization' => 0.05
        ];

        $totalScore = 0;
        foreach ($components as $component => $data) {
            $score = $data['score'] ?? 0;
            $weight = $weights[$component] ?? 0;
            $totalScore += $score * $weight;
        }

        return round($totalScore, 1);
    }

    /**
     * Generate performance recommendations
     */
    private function generatePerformanceRecommendations(array $components): array
    {
        $recommendations = [];

        // Resource optimization recommendations
        $resourceOpt = $components['resource_optimization'];
        if ($resourceOpt['score'] < 60) {
            if ($resourceOpt['preload_hints'] === 0) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => 'No preload hints detected',
                    'impact' => 'medium',
                    'fix' => 'Add <link rel="preload"> for critical resources like fonts and above-the-fold images'
                ];
            }
            if ($resourceOpt['preconnect_hints'] === 0) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'performance',
                    'message' => 'Consider adding preconnect hints',
                    'impact' => 'low',
                    'fix' => 'Add <link rel="preconnect"> for external domains you load resources from'
                ];
            }
        }

        // Image optimization recommendations
        $imageOpt = $components['image_optimization'];
        if ($imageOpt['score'] < 70 && $imageOpt['total_images'] > 0) {
            if ($imageOpt['lazy_loading_percentage'] < 50) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => 'Low lazy loading implementation',
                    'impact' => 'medium',
                    'fix' => 'Add loading="lazy" to images below the fold to improve initial page load'
                ];
            }
            if ($imageOpt['responsive_percentage'] < 50) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'performance',
                    'message' => 'Limited responsive image usage',
                    'impact' => 'medium',
                    'fix' => 'Use srcset attribute to serve appropriately sized images for different devices'
                ];
            }
        }

        // Script optimization recommendations
        $scriptOpt = $components['script_optimization'];
        if ($scriptOpt['score'] < 70) {
            if ($scriptOpt['optimized_percentage'] < 50) {
                $recommendations[] = [
                    'type' => 'error',
                    'category' => 'performance',
                    'message' => 'Too many render-blocking scripts',
                    'impact' => 'high',
                    'fix' => 'Add async or defer attributes to non-critical JavaScript files'
                ];
            }
            if ($scriptOpt['third_party_domains'] > 3) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => 'Too many third-party script domains',
                    'impact' => 'medium',
                    'fix' => 'Reduce the number of third-party services or consider self-hosting critical scripts'
                ];
            }
        }

        // Content optimization recommendations
        $contentOpt = $components['content_optimization'];
        if ($contentOpt['score'] < 60) {
            if ($contentOpt['html_size_kb'] > 200) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => 'Large HTML document size',
                    'impact' => 'medium',
                    'fix' => 'Consider reducing HTML size through minification and removing unnecessary content'
                ];
            }
            if ($contentOpt['dom_elements'] > 1500) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'performance',
                    'message' => 'High DOM complexity',
                    'impact' => 'low',
                    'fix' => 'Simplify DOM structure and consider virtualization for large lists'
                ];
            }
        }

        // Rendering optimization recommendations
        $renderingOpt = $components['rendering_optimization'];
        if ($renderingOpt['score'] < 60) {
            if ($renderingOpt['render_blocking_css'] > 2) {
                $recommendations[] = [
                    'type' => 'error',
                    'category' => 'performance',
                    'message' => 'Multiple render-blocking CSS files',
                    'impact' => 'high',
                    'fix' => 'Inline critical CSS and load non-critical CSS asynchronously'
                ];
            }
            if ($renderingOpt['cls_prevention_score'] < 80) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => 'Images missing width and height attributes',
                    'impact' => 'medium',
                    'fix' => 'Add explicit width and height attributes to images to prevent layout shift'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Calculate performance budget analysis
     */
    private function calculatePerformanceBudget(string $html, array $performanceElements): array
    {
        $htmlSize = strlen($html);
        $thirdPartyScripts = $performanceElements['third_party_scripts']['total_count'] ?? 0;

        // Estimate resource sizes (these would ideally come from actual measurements)
        $estimatedCssSize = substr_count($html, '<link') * 30; // 30KB average CSS file
        $estimatedJsSize = substr_count($html, '<script') * 50; // 50KB average JS file
        $estimatedImageSize = substr_count($html, '<img') * 100; // 100KB average image

        $estimatedTotalSize = $htmlSize + $estimatedCssSize + $estimatedJsSize + $estimatedImageSize;

        return [
            'estimated_total_size_kb' => round($estimatedTotalSize / 1024, 1),
            'html_size_kb' => round($htmlSize / 1024, 1),
            'estimated_css_size_kb' => round($estimatedCssSize / 1024, 1),
            'estimated_js_size_kb' => round($estimatedJsSize / 1024, 1),
            'estimated_image_size_kb' => round($estimatedImageSize / 1024, 1),
            'third_party_requests' => $thirdPartyScripts,
            'budget_status' => $this->getBudgetStatus($estimatedTotalSize),
            'recommendations' => $this->getBudgetRecommendations($estimatedTotalSize, $thirdPartyScripts)
        ];
    }

    /**
     * Get Core Web Vitals optimization hints
     */
    private function getCoreWebVitalsHints(string $html, array $performanceElements): array
    {
        return [
            'lcp_optimization' => [
                'preload_hero_image' => preg_match('/<link[^>]*rel=["\']preload["\'][^>]*as=["\']image["\'][^>]*>/i', $html) > 0,
                'optimize_critical_path' => preg_match('/<style[^>]*>[\s\S]*?<\/style>/i', $html) > 0,
                'server_side_rendering' => strpos($html, 'window.__INITIAL_STATE__') !== false || strpos($html, 'window.__PRELOADED_STATE__') !== false
            ],
            'fid_optimization' => [
                'defer_non_critical_js' => preg_match_all('/<script[^>]*defer[^>]*>/i', $html) > 0,
                'code_splitting_hints' => preg_match('/import\s*\(/i', $html) > 0,
                'minimize_main_thread_work' => ($performanceElements['third_party_scripts']['total_count'] ?? 0) < 5
            ],
            'cls_optimization' => [
                'image_dimensions_set' => $this->calculateImageDimensionRatio($html) > 80,
                'font_display_optimization' => preg_match('/font-display\s*:\s*(swap|fallback|optional)/i', $html) > 0,
                'reserve_space_dynamic_content' => preg_match('/min-height|aspect-ratio/i', $html) > 0
            ]
        ];
    }

    // Helper methods for identifying issues
    private function identifyResourceOptimizationIssues(array $preloadHints, array $resourceHints, bool $hasCriticalCss): array
    {
        $issues = [];

        if (($preloadHints['preload'] ?? 0) === 0) {
            $issues[] = 'No preload hints for critical resources';
        }
        if (($preloadHints['preconnect'] ?? 0) === 0) {
            $issues[] = 'Missing preconnect hints for external domains';
        }
        if (!$hasCriticalCss) {
            $issues[] = 'No critical CSS optimization detected';
        }

        return $issues;
    }

    private function identifyImageOptimizationIssues(int $total, int $lazy, int $responsive, int $webp, int $dimensioned): array
    {
        $issues = [];

        if ($total > 0) {
            if ($lazy / $total < 0.5) {
                $issues[] = 'Less than 50% of images use lazy loading';
            }
            if ($responsive / $total < 0.3) {
                $issues[] = 'Less than 30% of images are responsive';
            }
            if ($webp / $total < 0.2) {
                $issues[] = 'Less than 20% of images use modern formats';
            }
            if ($dimensioned / $total < 0.8) {
                $issues[] = 'More than 20% of images missing dimensions';
            }
        }

        return $issues;
    }

    private function identifyScriptOptimizationIssues(int $total, int $async, int $defer, int $inline, int $thirdPartyDomains): array
    {
        $issues = [];

        if ($total > 0 && ($async + $defer) / $total < 0.5) {
            $issues[] = 'More than 50% of scripts are render-blocking';
        }
        if ($inline > 3) {
            $issues[] = 'Too many inline scripts detected';
        }
        if ($thirdPartyDomains > 5) {
            $issues[] = 'Too many third-party script domains';
        }

        return $issues;
    }

    private function identifyCssOptimizationIssues(int $external, int $inline, bool $hasCriticalCss): array
    {
        $issues = [];

        if ($external > 5) {
            $issues[] = 'Too many external CSS files';
        }
        if (!$hasCriticalCss && $inline === 0) {
            $issues[] = 'No critical CSS optimization detected';
        }

        return $issues;
    }

    private function identifyContentOptimizationIssues(float $sizeKb, int $domElements, float $whitespaceRatio): array
    {
        $issues = [];

        if ($sizeKb > 500) {
            $issues[] = 'HTML document is very large (>500KB)';
        }
        if ($domElements > 1500) {
            $issues[] = 'High DOM complexity (>1500 elements)';
        }
        if ($whitespaceRatio > 0.2) {
            $issues[] = 'High amount of unnecessary whitespace';
        }

        return $issues;
    }

    private function identifyCacheOptimizationIssues(int $etag, int $lastModified, int $cacheControl, int $versioned, int $cdn): array
    {
        $issues = [];

        if (!$etag && !$lastModified && !$cacheControl) {
            $issues[] = 'No cache-related headers detected in HTML';
        }
        if ($versioned === 0) {
            $issues[] = 'No versioned assets detected for cache busting';
        }
        if ($cdn === 0) {
            $issues[] = 'No CDN usage detected';
        }

        return $issues;
    }

    private function identifyRenderingOptimizationIssues(int $blockingCss, int $blockingScripts, bool $preloadCss, bool $inlineCss, int $dimensioned, int $total): array
    {
        $issues = [];

        if ($blockingCss > 2) {
            $issues[] = 'Multiple render-blocking CSS files';
        }
        if ($blockingScripts > 1) {
            $issues[] = 'Render-blocking JavaScript detected';
        }
        if (!$preloadCss && !$inlineCss) {
            $issues[] = 'No critical CSS optimization';
        }
        if ($total > 0 && $dimensioned / $total < 0.8) {
            $issues[] = 'Images without dimensions may cause layout shift';
        }

        return $issues;
    }

    private function getBudgetStatus(int $totalSize): string
    {
        $sizeKb = $totalSize / 1024;

        if ($sizeKb <= 500) return 'Excellent';
        if ($sizeKb <= 1000) return 'Good';
        if ($sizeKb <= 2000) return 'Fair';
        return 'Poor';
    }

    private function getBudgetRecommendations(int $totalSize, int $thirdPartyRequests): array
    {
        $recommendations = [];
        $sizeKb = $totalSize / 1024;

        if ($sizeKb > 1000) {
            $recommendations[] = 'Total page weight exceeds 1MB - consider optimization';
        }
        if ($thirdPartyRequests > 10) {
            $recommendations[] = 'High number of third-party requests may impact performance';
        }

        return $recommendations;
    }

    private function calculateImageDimensionRatio(string $html): float
    {
        preg_match_all('/<img[^>]*>/i', $html, $allMatches);
        preg_match_all('/<img[^>]*width=["\'][^"\']+["\'][^>]*height=["\'][^"\']+["\'][^>]*>/i', $html, $dimensionedMatches);

        $total = count($allMatches[0]);
        $dimensioned = count($dimensionedMatches[0]);

        return $total > 0 ? ($dimensioned / $total) * 100 : 100;
    }
}