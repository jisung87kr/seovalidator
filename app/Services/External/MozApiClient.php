<?php

namespace App\Services\External;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Moz API Client for SEO metrics
 * Provides domain authority, page authority, and other Moz metrics
 */
class MozApiClient
{
    private Client $client;
    private string $accessId;
    private string $secretKey;
    private int $maxRetries;
    private int $retryDelay;
    private int $cacheTimeout;
    private string $baseUrl = 'https://lsapi.seomoz.com/v2/';

    public function __construct()
    {
        $this->accessId = config('services.moz.access_id');
        $this->secretKey = config('services.moz.secret_key');
        $this->maxRetries = config('services.moz.max_retries', 3);
        $this->retryDelay = config('services.moz.retry_delay', 1000);
        $this->cacheTimeout = config('services.moz.cache_timeout', 3600);

        if (empty($this->accessId) || empty($this->secretKey)) {
            throw new \InvalidArgumentException('Moz API credentials not configured');
        }

        $this->client = $this->createHttpClient();
    }

    /**
     * Get comprehensive domain metrics
     */
    public function getDomainMetrics(string $domain): array
    {
        $cacheKey = 'moz:domain:' . md5($domain);

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($domain) {
            Log::info('Starting Moz domain metrics analysis', ['domain' => $domain]);

            try {
                // Get URL metrics (includes DA, PA, spam score, etc.)
                $urlMetrics = $this->getUrlMetrics($domain);

                // Get link metrics
                $linkMetrics = $this->getLinkMetrics($domain);

                // Get keyword rankings (if available)
                $keywordMetrics = $this->getKeywordMetrics($domain);

                $results = [
                    'domain' => $domain,
                    'url_metrics' => $urlMetrics,
                    'link_metrics' => $linkMetrics,
                    'keyword_metrics' => $keywordMetrics,
                    'domain_authority_assessment' => $this->assessDomainAuthority($urlMetrics['domain_authority'] ?? 0),
                    'spam_risk_assessment' => $this->assessSpamRisk($urlMetrics['spam_score'] ?? 0),
                    'overall_seo_health' => $this->calculateOverallSeoHealth($urlMetrics, $linkMetrics),
                    'analyzed_at' => now()->toISOString(),
                    'cache_expires_at' => now()->addSeconds($this->cacheTimeout)->toISOString()
                ];

                Log::info('Moz domain metrics analysis completed', [
                    'domain' => $domain,
                    'domain_authority' => $urlMetrics['domain_authority'] ?? null,
                    'spam_score' => $urlMetrics['spam_score'] ?? null
                ]);

                return $results;

            } catch (\Exception $e) {
                Log::error('Moz domain metrics analysis failed', [
                    'domain' => $domain,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get URL metrics including Domain Authority, Page Authority, Spam Score
     */
    private function getUrlMetrics(string $url): array
    {
        try {
            $response = $this->client->post('url_metrics', [
                'json' => ['targets' => [$url]],
                'headers' => $this->getAuthHeaders()
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from Moz URL Metrics API');
            }

            $result = $data['results'][0] ?? [];

            return [
                'domain_authority' => $result['domain_authority'] ?? 0,
                'page_authority' => $result['page_authority'] ?? 0,
                'spam_score' => $result['spam_score'] ?? 0,
                'root_domains_to_root_domain' => $result['root_domains_to_root_domain'] ?? 0,
                'external_root_domains_to_root_domain' => $result['external_root_domains_to_root_domain'] ?? 0,
                'root_domains_to_page' => $result['root_domains_to_page'] ?? 0,
                'external_root_domains_to_page' => $result['external_root_domains_to_page'] ?? 0,
                'equity_links_to_page' => $result['equity_links_to_page'] ?? 0,
                'equity_links_to_root_domain' => $result['equity_links_to_root_domain'] ?? 0,
                'nofollow_equity_links_to_page' => $result['nofollow_equity_links_to_page'] ?? 0,
                'nofollow_equity_links_to_root_domain' => $result['nofollow_equity_links_to_root_domain'] ?? 0,
                'last_crawled' => $result['last_crawled'] ?? null
            ];

        } catch (RequestException $e) {
            Log::error('Moz URL Metrics API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'response_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);

            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 429) {
                throw new \RuntimeException('Moz API rate limit exceeded', 429);
            }

            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                throw new \RuntimeException('Moz API authentication failed', 401);
            }

            throw new \RuntimeException('Moz URL Metrics API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get link metrics and analysis
     */
    private function getLinkMetrics(string $domain): array
    {
        try {
            $response = $this->client->post('link_status', [
                'json' => ['targets' => [$domain]],
                'headers' => $this->getAuthHeaders()
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from Moz Link Status API');
            }

            $result = $data['results'][0] ?? [];

            // Get top linking domains
            $linkingDomains = $this->getTopLinkingDomains($domain);

            return [
                'status' => $result['status'] ?? 'unknown',
                'pages_crawled' => $result['pages_crawled'] ?? 0,
                'pages_in_index' => $result['pages_in_index'] ?? 0,
                'last_crawl_started' => $result['last_crawl_started'] ?? null,
                'last_crawl_completed' => $result['last_crawl_completed'] ?? null,
                'top_linking_domains' => $linkingDomains,
                'link_profile_health' => $this->assessLinkProfileHealth($result)
            ];

        } catch (RequestException $e) {
            Log::warning('Moz Link Status API request failed, using fallback', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'unavailable',
                'error' => 'Link metrics temporarily unavailable'
            ];
        }
    }

    /**
     * Get top linking domains
     */
    private function getTopLinkingDomains(string $domain, int $limit = 10): array
    {
        try {
            $response = $this->client->post('linking_domains', [
                'json' => [
                    'target' => $domain,
                    'limit' => $limit
                ],
                'headers' => $this->getAuthHeaders()
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            $results = $data['results'] ?? [];
            $linkingDomains = [];

            foreach ($results as $result) {
                $linkingDomains[] = [
                    'domain' => $result['source_domain'] ?? '',
                    'domain_authority' => $result['source_domain_authority'] ?? 0,
                    'spam_score' => $result['source_spam_score'] ?? 0,
                    'equity_links' => $result['equity_links'] ?? 0,
                    'quality_score' => $this->calculateLinkQuality(
                        $result['source_domain_authority'] ?? 0,
                        $result['source_spam_score'] ?? 0
                    )
                ];
            }

            // Sort by quality score
            usort($linkingDomains, function ($a, $b) {
                return $b['quality_score'] <=> $a['quality_score'];
            });

            return $linkingDomains;

        } catch (RequestException $e) {
            Log::warning('Moz Linking Domains API request failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get keyword metrics (if available)
     */
    private function getKeywordMetrics(string $domain): array
    {
        // Note: Keyword data requires additional Moz subscription
        // This is a placeholder for when keyword tracking is available
        return [
            'ranking_keywords_estimated' => null,
            'top_ranking_keywords' => [],
            'organic_visibility_score' => null,
            'keyword_difficulty_average' => null,
            'note' => 'Keyword metrics require Moz Pro subscription'
        ];
    }

    /**
     * Create HTTP client with authentication and retry logic
     */
    private function createHttpClient(): Client
    {
        $stack = HandlerStack::create();

        // Add retry middleware
        $stack->push(Middleware::retry(
            function ($retries, RequestInterface $request, ResponseInterface $response = null, RequestException $exception = null) {
                if ($retries >= $this->maxRetries) {
                    return false;
                }

                if ($exception) {
                    // Don't retry on authentication errors
                    if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 401) {
                        return false;
                    }

                    // Retry on rate limits with longer delay
                    if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 429) {
                        usleep($this->retryDelay * 5000 * ($retries + 1)); // Longer backoff for rate limits
                        return true;
                    }

                    // Retry on connection errors
                    if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                        usleep($this->retryDelay * 1000 * ($retries + 1));
                        return true;
                    }

                    // Retry on server errors (5xx)
                    if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 500) {
                        usleep($this->retryDelay * 1000 * ($retries + 1));
                        return true;
                    }
                }

                return false;
            }
        ));

        return new Client([
            'base_uri' => $this->baseUrl,
            'handler' => $stack,
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'SEO-Validator/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Get authentication headers for Moz API
     */
    private function getAuthHeaders(): array
    {
        $timestamp = time();
        $string_to_sign = $this->accessId . "\n" . $timestamp;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->secretKey, true));

        return [
            'Authorization' => 'Basic ' . base64_encode($this->accessId . ':' . $signature . ':' . $timestamp)
        ];
    }

    /**
     * Assessment methods
     */
    private function assessDomainAuthority(int $da): array
    {
        $assessment = 'Poor';
        $recommendation = '';

        if ($da >= 80) {
            $assessment = 'Excellent';
            $recommendation = 'Maintain high-quality content and backlink profile';
        } elseif ($da >= 60) {
            $assessment = 'Good';
            $recommendation = 'Focus on acquiring high-quality backlinks from authoritative domains';
        } elseif ($da >= 40) {
            $assessment = 'Fair';
            $recommendation = 'Improve content quality and work on link building strategy';
        } elseif ($da >= 20) {
            $assessment = 'Below Average';
            $recommendation = 'Develop comprehensive SEO strategy focusing on content and technical optimization';
        } else {
            $assessment = 'Poor';
            $recommendation = 'Start with technical SEO improvements and create high-quality, relevant content';
        }

        return [
            'score' => $da,
            'assessment' => $assessment,
            'recommendation' => $recommendation,
            'percentile' => $this->getDomainAuthorityPercentile($da)
        ];
    }

    private function assessSpamRisk(int $spamScore): array
    {
        $risk = 'Low';
        $recommendation = '';

        if ($spamScore >= 70) {
            $risk = 'Very High';
            $recommendation = 'Immediate action required: disavow toxic backlinks and improve link profile';
        } elseif ($spamScore >= 50) {
            $risk = 'High';
            $recommendation = 'Review backlink profile and consider disavowing low-quality links';
        } elseif ($spamScore >= 30) {
            $risk = 'Medium';
            $recommendation = 'Monitor backlink quality and focus on acquiring high-quality links';
        } elseif ($spamScore >= 10) {
            $risk = 'Low';
            $recommendation = 'Continue current link building practices while monitoring quality';
        } else {
            $risk = 'Very Low';
            $recommendation = 'Excellent link profile - maintain current quality standards';
        }

        return [
            'score' => $spamScore,
            'risk_level' => $risk,
            'recommendation' => $recommendation,
            'action_required' => $spamScore >= 50
        ];
    }

    private function calculateOverallSeoHealth(array $urlMetrics, array $linkMetrics): array
    {
        $da = $urlMetrics['domain_authority'] ?? 0;
        $spamScore = $urlMetrics['spam_score'] ?? 0;
        $backlinks = $urlMetrics['external_root_domains_to_root_domain'] ?? 0;

        // Calculate health score (0-100)
        $healthScore = 0;

        // Domain Authority contribution (40%)
        $healthScore += ($da / 100) * 40;

        // Spam Score penalty (30%)
        $spamPenalty = ($spamScore / 100) * 30;
        $healthScore += (30 - $spamPenalty);

        // Backlink profile contribution (30%)
        if ($backlinks > 1000) {
            $healthScore += 30;
        } elseif ($backlinks > 500) {
            $healthScore += 25;
        } elseif ($backlinks > 100) {
            $healthScore += 20;
        } elseif ($backlinks > 50) {
            $healthScore += 15;
        } elseif ($backlinks > 10) {
            $healthScore += 10;
        } else {
            $healthScore += 5;
        }

        $healthScore = round(max(0, min(100, $healthScore)));

        $grade = 'F';
        if ($healthScore >= 90) $grade = 'A';
        elseif ($healthScore >= 80) $grade = 'B';
        elseif ($healthScore >= 70) $grade = 'C';
        elseif ($healthScore >= 60) $grade = 'D';

        return [
            'score' => $healthScore,
            'grade' => $grade,
            'components' => [
                'domain_authority' => $da,
                'spam_risk' => $spamScore,
                'backlink_count' => $backlinks
            ],
            'improvement_areas' => $this->identifyImprovementAreas($da, $spamScore, $backlinks)
        ];
    }

    private function assessLinkProfileHealth(array $linkData): array
    {
        $pagesInIndex = $linkData['pages_in_index'] ?? 0;
        $pagesCrawled = $linkData['pages_crawled'] ?? 0;

        $indexRatio = $pagesCrawled > 0 ? ($pagesInIndex / $pagesCrawled) * 100 : 0;

        $health = 'Unknown';
        if ($indexRatio >= 80) {
            $health = 'Excellent';
        } elseif ($indexRatio >= 60) {
            $health = 'Good';
        } elseif ($indexRatio >= 40) {
            $health = 'Fair';
        } elseif ($indexRatio > 0) {
            $health = 'Poor';
        }

        return [
            'index_ratio' => round($indexRatio, 1),
            'health_status' => $health,
            'pages_crawled' => $pagesCrawled,
            'pages_indexed' => $pagesInIndex
        ];
    }

    private function calculateLinkQuality(int $domainAuthority, int $spamScore): float
    {
        // Quality score based on DA and inverse spam score
        $qualityScore = ($domainAuthority / 100) * 70; // DA contributes 70%
        $spamPenalty = ($spamScore / 100) * 30; // Spam score penalty 30%
        return round(max(0, $qualityScore - $spamPenalty), 1);
    }

    private function getDomainAuthorityPercentile(int $da): string
    {
        if ($da >= 80) return 'Top 1%';
        if ($da >= 70) return 'Top 5%';
        if ($da >= 60) return 'Top 10%';
        if ($da >= 50) return 'Top 25%';
        if ($da >= 40) return 'Top 50%';
        return 'Bottom 50%';
    }

    private function identifyImprovementAreas(int $da, int $spamScore, int $backlinks): array
    {
        $areas = [];

        if ($da < 40) {
            $areas[] = 'Improve domain authority through high-quality content and link building';
        }

        if ($spamScore > 30) {
            $areas[] = 'Reduce spam score by disavowing toxic backlinks';
        }

        if ($backlinks < 50) {
            $areas[] = 'Increase backlink acquisition from relevant, authoritative domains';
        }

        return $areas;
    }
}