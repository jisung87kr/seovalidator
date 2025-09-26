<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SEO Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the SEO analysis engine.
    |
    */

    'cache_ttl' => env('SEO_CACHE_TTL', 3600), // 1 hour default

    'crawler' => [
        'default_timeout' => env('SEO_CRAWLER_TIMEOUT', 30000), // 30 seconds
        'max_redirects' => env('SEO_CRAWLER_MAX_REDIRECTS', 10),
        'user_agent' => env('SEO_CRAWLER_USER_AGENT', 'SEO Validator Bot/1.0 (Googlebot-compatible)'),
        'javascript_enabled' => env('SEO_CRAWLER_JAVASCRIPT', true),
        'wait_for_load' => env('SEO_CRAWLER_WAIT_FOR_LOAD', 3000), // 3 seconds
    ],

    'scoring' => [
        'weights' => [
            'title' => 20,
            'meta_description' => 15,
            'headings' => 15,
            'content' => 20,
            'images' => 10,
            'links' => 8,
            'technical' => 7,
            'social_media' => 3,
            'structured_data' => 2,
        ],
    ],

    'validation' => [
        'max_url_length' => 2048,
        'allowed_schemes' => ['http', 'https'],
        'blocked_domains' => [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
        ],
        'blocked_tlds' => [
            'test',
            'localhost',
            'local',
        ],
    ],

    'performance' => [
        'max_analysis_time' => env('SEO_MAX_ANALYSIS_TIME', 60), // 60 seconds
        'batch_size' => env('SEO_BATCH_SIZE', 10),
    ],
];