<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the Redis connections below you wish
    | to use as your default connection for all Redis work. Of course
    | you may use many connections at once using the Redis library.
    |
    */

    'default' => env('REDIS_CLIENT', 'predis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the Redis connections setup for your application.
    | Of course, examples of configuring each available driver is shown
    | below to make development simple. You can configure these drivers
    | with Laravel cache, session, and queue compatibility out of the box.
    |
    */

    'connections' => [

        'default' => [
            'client' => env('REDIS_CLIENT', 'predis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'read_timeout' => env('REDIS_READ_TIMEOUT', '60'),
            'context' => [],
        ],

        'cache' => [
            'client' => env('REDIS_CLIENT', 'predis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'read_timeout' => env('REDIS_READ_TIMEOUT', '60'),
            'context' => [],
        ],

        'sessions' => [
            'client' => env('REDIS_CLIENT', 'predis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'read_timeout' => env('REDIS_READ_TIMEOUT', '60'),
            'context' => [],
        ],

        'horizon' => [
            'client' => env('REDIS_CLIENT', 'predis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_HORIZON_DB', '3'),
            'read_timeout' => env('REDIS_READ_TIMEOUT', '60'),
            'context' => [],
        ],

    ],

];