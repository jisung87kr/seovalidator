<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout in seconds for web crawling operations
    |
    */
    'timeout' => env('CRAWLER_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | User Agents
    |--------------------------------------------------------------------------
    |
    | List of User-Agent strings to rotate through for crawling
    |
    */
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Caching configuration for crawl results
    |
    */
    'cache' => [
        'enabled' => env('CRAWLER_CACHE_ENABLED', true),
        'ttl' => env('CRAWLER_CACHE_TTL', 86400), // 24 hours in seconds
        'prefix' => 'seo_crawl:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for retry logic on failed crawls
    |
    */
    'retry' => [
        'max_attempts' => env('CRAWLER_MAX_RETRY', 3),
        'delay_seconds' => env('CRAWLER_RETRY_DELAY', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browsershot Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Spatie Browsershot package
    |
    */
    'browsershot' => [
        'node_binary' => env('NODE_BINARY', '/usr/bin/node'),
        'npm_binary' => env('NPM_BINARY', '/usr/bin/npm'),
        'chrome_binary' => env('CHROME_BINARY', null),
        'args' => [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-software-rasterizer',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data extraction from crawled pages
    |
    */
    'extraction' => [
        'max_description_length' => 160,
        'max_title_length' => 60,
        'extract_images' => true,
        'extract_links' => true,
        'extract_meta_tags' => true,
        'extract_headings' => true,
        'follow_redirects' => true,
        'max_redirects' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings to be respectful to target websites
    |
    */
    'rate_limit' => [
        'enabled' => env('CRAWLER_RATE_LIMIT_ENABLED', true),
        'delay_between_requests' => env('CRAWLER_DELAY_MS', 1000), // milliseconds
    ],
];