<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Services Configuration
    |--------------------------------------------------------------------------
    */

    'google' => [
        'pagespeed_api_key' => env('GOOGLE_PAGESPEED_API_KEY'),
        'pagespeed_max_retries' => env('GOOGLE_PAGESPEED_MAX_RETRIES', 3),
        'pagespeed_retry_delay' => env('GOOGLE_PAGESPEED_RETRY_DELAY', 1000),
        'pagespeed_cache_timeout' => env('GOOGLE_PAGESPEED_CACHE_TIMEOUT', 3600),
        'pagespeed_rate_limit_per_day' => env('GOOGLE_PAGESPEED_RATE_LIMIT_PER_DAY', 25000),
        'pagespeed_rate_limit_per_minute' => env('GOOGLE_PAGESPEED_RATE_LIMIT_PER_MINUTE', 240),
    ],

    'moz' => [
        'access_id' => env('MOZ_ACCESS_ID'),
        'secret_key' => env('MOZ_SECRET_KEY'),
        'max_retries' => env('MOZ_MAX_RETRIES', 3),
        'retry_delay' => env('MOZ_RETRY_DELAY', 1000),
        'cache_timeout' => env('MOZ_CACHE_TIMEOUT', 3600),
        'rate_limit_per_month' => env('MOZ_RATE_LIMIT_PER_MONTH', 10000),
        'rate_limit_per_minute' => env('MOZ_RATE_LIMIT_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'sentry_dsn' => env('SENTRY_LARAVEL_DSN'),
        'sentry_environment' => env('SENTRY_ENVIRONMENT', 'production'),
        'sentry_release' => env('SENTRY_RELEASE'),
        'alert_thresholds' => [
            'response_time_ms' => env('ALERT_THRESHOLD_RESPONSE_TIME', 1000),
            'error_rate_percent' => env('ALERT_THRESHOLD_ERROR_RATE', 5),
            'queue_depth' => env('ALERT_THRESHOLD_QUEUE_DEPTH', 100),
            'memory_usage_percent' => env('ALERT_THRESHOLD_MEMORY_USAGE', 85),
            'disk_usage_percent' => env('ALERT_THRESHOLD_DISK_USAGE', 90),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External HTTP Client Configuration
    |--------------------------------------------------------------------------
    */

    'http_client' => [
        'timeout' => env('HTTP_CLIENT_TIMEOUT', 30),
        'connect_timeout' => env('HTTP_CLIENT_CONNECT_TIMEOUT', 10),
        'max_redirects' => env('HTTP_CLIENT_MAX_REDIRECTS', 3),
        'user_agent' => env('HTTP_CLIENT_USER_AGENT', 'SEO-Validator/1.0'),
        'verify_ssl' => env('HTTP_CLIENT_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'external_api_integration' => env('FEATURE_EXTERNAL_API_INTEGRATION', true),
        'advanced_caching' => env('FEATURE_ADVANCED_CACHING', true),
        'performance_monitoring' => env('FEATURE_PERFORMANCE_MONITORING', true),
        'load_testing' => env('FEATURE_LOAD_TESTING', false),
        'competitive_analysis' => env('FEATURE_COMPETITIVE_ANALYSIS', true),
        'batch_analysis' => env('FEATURE_BATCH_ANALYSIS', true),
        'webhook_notifications' => env('FEATURE_WEBHOOK_NOTIFICATIONS', true),
        'api_versioning' => env('FEATURE_API_VERSIONING', true),
    ],

];
