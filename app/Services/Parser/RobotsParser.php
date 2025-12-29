<?php

namespace App\Services\Parser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RobotsParser
{
    /**
     * Parse robots.txt and meta robots tags
     */
    public function parseRobots(string $url, ?string $metaRobots = null): array
    {
        $robotsData = [
            'is_allowed' => true,
            'robots_txt_exists' => false,
            'meta_robots' => $metaRobots,
            'disallow_patterns' => [],
            'allow_patterns' => [],
            'sitemap_urls' => [],
            'crawl_delay' => null,
            'user_agent_rules' => [],
            'disallow_reason' => null
        ];

        try {
            // Get the base URL for robots.txt
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $robotsUrl = $baseUrl . '/robots.txt';
            
            // Fetch robots.txt
            $response = Http::timeout(10)->get($robotsUrl);
            
            if ($response->successful()) {
                $robotsData['robots_txt_exists'] = true;
                $robotsTxt = $response->body();
                
                // Parse robots.txt content
                $parsedRobots = $this->parseRobotsTxt($robotsTxt);
                $robotsData = array_merge($robotsData, $parsedRobots);
                
                // Check if current URL is allowed
                $path = $parsedUrl['path'] ?? '/';
                $isAllowedByRobotsTxt = $this->checkUrlAllowed($path, $parsedRobots);
                
                if (!$isAllowedByRobotsTxt) {
                    $robotsData['is_allowed'] = false;
                    $robotsData['disallow_reason'] = 'Blocked by robots.txt';
                }
            }
            
            // Check meta robots tag
            if ($metaRobots) {
                $metaRobotsLower = strtolower($metaRobots);
                if (str_contains($metaRobotsLower, 'noindex') || 
                    str_contains($metaRobotsLower, 'none')) {
                    $robotsData['is_allowed'] = false;
                    $robotsData['disallow_reason'] = $robotsData['disallow_reason'] 
                        ? $robotsData['disallow_reason'] . ' and meta robots tag' 
                        : 'Blocked by meta robots tag';
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to fetch or parse robots.txt', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }
        
        return $robotsData;
    }
    
    /**
     * Parse robots.txt content
     */
    private function parseRobotsTxt(string $content): array
    {
        $lines = explode("\n", $content);
        $result = [
            'disallow_patterns' => [],
            'allow_patterns' => [],
            'sitemap_urls' => [],
            'crawl_delay' => null,
            'user_agent_rules' => []
        ];
        
        $currentUserAgent = '*';
        $inRelevantSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Parse line
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $currentUserAgent = trim($matches[1]);
                $inRelevantSection = ($currentUserAgent === '*' || 
                                     stripos($currentUserAgent, 'googlebot') !== false ||
                                     stripos($currentUserAgent, 'bingbot') !== false);
                
                if ($inRelevantSection && !isset($result['user_agent_rules'][$currentUserAgent])) {
                    $result['user_agent_rules'][$currentUserAgent] = [
                        'disallow' => [],
                        'allow' => []
                    ];
                }
            } elseif ($inRelevantSection) {
                if (preg_match('/^Disallow:\s*(.+)$/i', $line, $matches)) {
                    $path = trim($matches[1]);
                    if (!empty($path)) {
                        $result['disallow_patterns'][] = $path;
                        $result['user_agent_rules'][$currentUserAgent]['disallow'][] = $path;
                    }
                } elseif (preg_match('/^Allow:\s*(.+)$/i', $line, $matches)) {
                    $path = trim($matches[1]);
                    if (!empty($path)) {
                        $result['allow_patterns'][] = $path;
                        $result['user_agent_rules'][$currentUserAgent]['allow'][] = $path;
                    }
                } elseif (preg_match('/^Crawl-delay:\s*(\d+)$/i', $line, $matches)) {
                    $result['crawl_delay'] = intval($matches[1]);
                }
            }
            
            // Sitemap is global regardless of user-agent
            if (preg_match('/^Sitemap:\s*(.+)$/i', $line, $matches)) {
                $result['sitemap_urls'][] = trim($matches[1]);
            }
        }
        
        // Remove duplicates
        $result['disallow_patterns'] = array_unique($result['disallow_patterns']);
        $result['allow_patterns'] = array_unique($result['allow_patterns']);
        $result['sitemap_urls'] = array_unique($result['sitemap_urls']);
        
        return $result;
    }
    
    /**
     * Check if URL is allowed according to robots.txt rules
     */
    private function checkUrlAllowed(string $path, array $robotsData): bool
    {
        // First check allow patterns (they override disallow)
        foreach ($robotsData['allow_patterns'] as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }
        
        // Then check disallow patterns
        foreach ($robotsData['disallow_patterns'] as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return false;
            }
        }
        
        // Default is allowed
        return true;
    }
    
    /**
     * Check if path matches robots.txt pattern
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Handle wildcard patterns
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('$', '\$', $pattern);
        
        // Pattern should match from the beginning
        return preg_match('#^' . $pattern . '#', $path) === 1;
    }
}