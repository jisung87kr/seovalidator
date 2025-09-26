<?php

namespace App\Services\Technical;

use App\Services\Technical\PageSpeedService;
use App\Services\Technical\SecurityService;
use App\Services\Technical\SitemapAnalyzerService;
use App\Services\Technical\CanonicalUrlService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Main orchestrator for technical SEO analysis
 * Coordinates all technical SEO validation services including page speed,
 * mobile responsiveness, SSL validation, structured data, and sitemap analysis
 */
class TechnicalSeoService
{
    public function __construct(
        private PageSpeedService $pageSpeedService,
        private SecurityService $securityService,
        private SitemapAnalyzerService $sitemapAnalyzerService,
        private CanonicalUrlService $canonicalUrlService
    ) {}

    /**
     * Perform comprehensive technical SEO analysis
     */
    public function analyze(string $url, string $html, array $options = []): array
    {
        Log::info('Starting technical SEO analysis', [
            'url' => $url,
            'options' => $options
        ]);

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'page_speed' => null,
            'mobile_optimization' => null,
            'security' => null,
            'sitemap_analysis' => null,
            'canonical_urls' => null,
            'structured_data' => null,
            'technical_score' => 0,
            'recommendations' => [],
            'errors' => []
        ];

        // Page Speed Analysis (Core Web Vitals)
        try {
            if ($options['include_page_speed'] ?? true) {
                Log::debug('Analyzing page speed metrics', ['url' => $url]);
                $results['page_speed'] = $this->pageSpeedService->analyze($url, $options);
            }
        } catch (Exception $e) {
            Log::warning('Page speed analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'page_speed',
                'message' => 'Page speed analysis failed: ' . $e->getMessage()
            ];
        }

        // Mobile Optimization Analysis
        try {
            if ($options['include_mobile_analysis'] ?? true) {
                Log::debug('Analyzing mobile optimization', ['url' => $url]);
                $results['mobile_optimization'] = $this->pageSpeedService->analyzeMobile($url, $html, $options);
            }
        } catch (Exception $e) {
            Log::warning('Mobile optimization analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'mobile_optimization',
                'message' => 'Mobile optimization analysis failed: ' . $e->getMessage()
            ];
        }

        // Security Analysis (SSL, HTTPS, Headers)
        try {
            if ($options['include_security_analysis'] ?? true) {
                Log::debug('Analyzing security factors', ['url' => $url]);
                $results['security'] = $this->securityService->analyze($url, $html, $options);
            }
        } catch (Exception $e) {
            Log::warning('Security analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'security',
                'message' => 'Security analysis failed: ' . $e->getMessage()
            ];
        }

        // Sitemap and Robots.txt Analysis
        try {
            if ($options['include_sitemap_analysis'] ?? true) {
                Log::debug('Analyzing sitemap and robots.txt', ['url' => $url]);
                $results['sitemap_analysis'] = $this->sitemapAnalyzerService->analyze($url, $options);
            }
        } catch (Exception $e) {
            Log::warning('Sitemap analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'sitemap',
                'message' => 'Sitemap analysis failed: ' . $e->getMessage()
            ];
        }

        // Canonical URL Analysis
        try {
            if ($options['include_canonical_analysis'] ?? true) {
                Log::debug('Analyzing canonical URLs', ['url' => $url]);
                $results['canonical_urls'] = $this->canonicalUrlService->analyze($url, $html, $options);
            }
        } catch (Exception $e) {
            Log::warning('Canonical URL analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'canonical',
                'message' => 'Canonical URL analysis failed: ' . $e->getMessage()
            ];
        }

        // Structured Data Analysis (JSON-LD, Microdata)
        try {
            if ($options['include_structured_data'] ?? true) {
                Log::debug('Analyzing structured data', ['url' => $url]);
                $results['structured_data'] = $this->analyzeStructuredData($html, $url);
            }
        } catch (Exception $e) {
            Log::warning('Structured data analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            $results['errors'][] = [
                'service' => 'structured_data',
                'message' => 'Structured data analysis failed: ' . $e->getMessage()
            ];
        }

        // Calculate overall technical score and generate recommendations
        $results['technical_score'] = $this->calculateTechnicalScore($results);
        $results['recommendations'] = $this->generateTechnicalRecommendations($results);

        Log::info('Technical SEO analysis completed', [
            'url' => $url,
            'technical_score' => $results['technical_score'],
            'error_count' => count($results['errors'])
        ]);

        return $results;
    }

    /**
     * Analyze structured data in HTML content
     */
    private function analyzeStructuredData(string $html, string $url): array
    {
        $results = [
            'json_ld' => $this->extractJsonLd($html),
            'microdata' => $this->extractMicrodata($html),
            'schema_validation' => [],
            'rich_snippets_eligible' => false,
            'recommendations' => []
        ];

        // Validate JSON-LD schemas
        foreach ($results['json_ld'] as $index => $jsonLd) {
            $validation = $this->validateJsonLdSchema($jsonLd);
            $results['schema_validation'][] = [
                'type' => 'json-ld',
                'index' => $index,
                'schema_type' => $jsonLd['@type'] ?? 'unknown',
                'is_valid' => $validation['is_valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings']
            ];
        }

        // Check rich snippets eligibility
        $results['rich_snippets_eligible'] = $this->checkRichSnippetsEligibility($results);

        // Generate structured data recommendations
        $results['recommendations'] = $this->generateStructuredDataRecommendations($results);

        return $results;
    }

    /**
     * Extract JSON-LD structured data from HTML
     */
    private function extractJsonLd(string $html): array
    {
        $jsonLdData = [];

        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            foreach ($matches[1] as $jsonString) {
                try {
                    $decoded = json_decode(trim($jsonString), true, 512, JSON_THROW_ON_ERROR);
                    if ($decoded) {
                        $jsonLdData[] = $decoded;
                    }
                } catch (Exception $e) {
                    Log::warning('Invalid JSON-LD found', [
                        'json' => substr($jsonString, 0, 200),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $jsonLdData;
    }

    /**
     * Extract microdata from HTML
     */
    private function extractMicrodata(string $html): array
    {
        $microdata = [];

        // Parse HTML and extract microdata attributes
        if (preg_match_all('/itemscope[^>]*itemtype=["\']([^"\']+)["\'][^>]*>/', $html, $matches)) {
            foreach ($matches[1] as $itemType) {
                $microdata[] = [
                    'itemtype' => $itemType,
                    'schema_type' => basename(parse_url($itemType, PHP_URL_PATH))
                ];
            }
        }

        return $microdata;
    }

    /**
     * Validate JSON-LD schema structure
     */
    private function validateJsonLdSchema(array $jsonLd): array
    {
        $errors = [];
        $warnings = [];

        // Check for required @context
        if (!isset($jsonLd['@context'])) {
            $errors[] = 'Missing @context property';
        }

        // Check for @type
        if (!isset($jsonLd['@type'])) {
            $errors[] = 'Missing @type property';
        }

        // Validate schema-specific requirements based on @type
        $schemaType = $jsonLd['@type'] ?? '';
        switch ($schemaType) {
            case 'Organization':
                if (!isset($jsonLd['name'])) {
                    $errors[] = 'Organization schema missing required "name" property';
                }
                if (!isset($jsonLd['url'])) {
                    $warnings[] = 'Organization schema missing recommended "url" property';
                }
                break;

            case 'LocalBusiness':
                if (!isset($jsonLd['name'])) {
                    $errors[] = 'LocalBusiness schema missing required "name" property';
                }
                if (!isset($jsonLd['address'])) {
                    $errors[] = 'LocalBusiness schema missing required "address" property';
                }
                if (!isset($jsonLd['telephone'])) {
                    $warnings[] = 'LocalBusiness schema missing recommended "telephone" property';
                }
                break;

            case 'Article':
                if (!isset($jsonLd['headline'])) {
                    $errors[] = 'Article schema missing required "headline" property';
                }
                if (!isset($jsonLd['author'])) {
                    $warnings[] = 'Article schema missing recommended "author" property';
                }
                if (!isset($jsonLd['datePublished'])) {
                    $warnings[] = 'Article schema missing recommended "datePublished" property';
                }
                break;

            case 'Product':
                if (!isset($jsonLd['name'])) {
                    $errors[] = 'Product schema missing required "name" property';
                }
                if (!isset($jsonLd['offers'])) {
                    $warnings[] = 'Product schema missing recommended "offers" property';
                }
                break;

            case 'BreadcrumbList':
                if (!isset($jsonLd['itemListElement'])) {
                    $errors[] = 'BreadcrumbList schema missing required "itemListElement" property';
                }
                break;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Check if content is eligible for rich snippets
     */
    private function checkRichSnippetsEligibility(array $structuredData): bool
    {
        $eligibleTypes = ['Organization', 'LocalBusiness', 'Article', 'Product', 'Recipe', 'Event', 'Review', 'BreadcrumbList'];

        foreach ($structuredData['json_ld'] as $jsonLd) {
            if (in_array($jsonLd['@type'] ?? '', $eligibleTypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate structured data recommendations
     */
    private function generateStructuredDataRecommendations(array $structuredData): array
    {
        $recommendations = [];

        if (empty($structuredData['json_ld']) && empty($structuredData['microdata'])) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'No structured data found',
                'impact' => 'high',
                'fix' => 'Add JSON-LD structured data to improve search visibility'
            ];
        }

        foreach ($structuredData['schema_validation'] as $validation) {
            if (!$validation['is_valid']) {
                foreach ($validation['errors'] as $error) {
                    $recommendations[] = [
                        'type' => 'error',
                        'message' => "Schema validation error in {$validation['schema_type']}: {$error}",
                        'impact' => 'medium',
                        'fix' => 'Fix the schema validation error'
                    ];
                }
            }

            foreach ($validation['warnings'] as $warning) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => "Schema improvement for {$validation['schema_type']}: {$warning}",
                    'impact' => 'low',
                    'fix' => 'Add the recommended property to improve schema completeness'
                ];
            }
        }

        if (!$structuredData['rich_snippets_eligible']) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Page not eligible for rich snippets',
                'impact' => 'medium',
                'fix' => 'Add structured data types that support rich snippets (Organization, Article, Product, etc.)'
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate overall technical SEO score
     */
    private function calculateTechnicalScore(array $results): int
    {
        $score = 0;
        $maxScore = 0;

        // Page Speed Score (25%)
        if (isset($results['page_speed']['performance_score'])) {
            $score += $results['page_speed']['performance_score'] * 0.25;
            $maxScore += 25;
        }

        // Security Score (20%)
        if (isset($results['security']['security_score'])) {
            $score += $results['security']['security_score'] * 0.20;
            $maxScore += 20;
        }

        // Mobile Score (20%)
        if (isset($results['mobile_optimization']['mobile_score'])) {
            $score += $results['mobile_optimization']['mobile_score'] * 0.20;
            $maxScore += 20;
        }

        // Structured Data Score (15%)
        if (isset($results['structured_data'])) {
            $structuredDataScore = $this->calculateStructuredDataScore($results['structured_data']);
            $score += $structuredDataScore * 0.15;
            $maxScore += 15;
        }

        // Canonical URLs Score (10%)
        if (isset($results['canonical_urls']['canonical_score'])) {
            $score += $results['canonical_urls']['canonical_score'] * 0.10;
            $maxScore += 10;
        }

        // Sitemap Score (10%)
        if (isset($results['sitemap_analysis']['sitemap_score'])) {
            $score += $results['sitemap_analysis']['sitemap_score'] * 0.10;
            $maxScore += 10;
        }

        return $maxScore > 0 ? min(100, round(($score / $maxScore) * 100)) : 0;
    }

    /**
     * Calculate structured data score
     */
    private function calculateStructuredDataScore(array $structuredData): int
    {
        $score = 0;

        // Base score for having structured data
        if (!empty($structuredData['json_ld']) || !empty($structuredData['microdata'])) {
            $score += 40;
        }

        // Bonus for valid schemas
        $validSchemas = 0;
        $totalSchemas = count($structuredData['schema_validation']);

        foreach ($structuredData['schema_validation'] as $validation) {
            if ($validation['is_valid']) {
                $validSchemas++;
            }
        }

        if ($totalSchemas > 0) {
            $score += (($validSchemas / $totalSchemas) * 30);
        }

        // Bonus for rich snippets eligibility
        if ($structuredData['rich_snippets_eligible']) {
            $score += 30;
        }

        return min(100, round($score));
    }

    /**
     * Generate technical SEO recommendations
     */
    private function generateTechnicalRecommendations(array $results): array
    {
        $recommendations = [];

        // Collect recommendations from all services
        if (isset($results['page_speed']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['page_speed']['recommendations']);
        }

        if (isset($results['security']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['security']['recommendations']);
        }

        if (isset($results['mobile_optimization']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['mobile_optimization']['recommendations']);
        }

        if (isset($results['sitemap_analysis']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['sitemap_analysis']['recommendations']);
        }

        if (isset($results['canonical_urls']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['canonical_urls']['recommendations']);
        }

        if (isset($results['structured_data']['recommendations'])) {
            $recommendations = array_merge($recommendations, $results['structured_data']['recommendations']);
        }

        // Add general technical recommendations based on overall score
        if ($results['technical_score'] < 70) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'technical',
                'message' => 'Technical SEO score needs improvement',
                'impact' => 'high',
                'fix' => 'Focus on improving page speed, security, and mobile optimization'
            ];
        }

        // Sort by impact (high, medium, low)
        usort($recommendations, function ($a, $b) {
            $impactOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($impactOrder[$b['impact']] ?? 0) <=> ($impactOrder[$a['impact']] ?? 0);
        });

        return $recommendations;
    }
}