<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.auth' => \App\Http\Middleware\Api\ApiKeyAuthentication::class,
            'api.rate_limit' => \App\Http\Middleware\Api\ApiRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => $e->errors()
                    ],
                    'meta' => [
                        'version' => '1.0',
                        'timestamp' => now()->toISOString(),
                        'request_id' => \Illuminate\Support\Str::uuid()->toString()
                    ]
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Resource not found.',
                        'details' => null
                    ],
                    'meta' => [
                        'version' => '1.0',
                        'timestamp' => now()->toISOString(),
                        'request_id' => \Illuminate\Support\Str::uuid()->toString()
                    ]
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ENDPOINT_NOT_FOUND',
                        'message' => 'The requested endpoint was not found.',
                        'details' => null
                    ],
                    'meta' => [
                        'version' => '1.0',
                        'timestamp' => now()->toISOString(),
                        'request_id' => \Illuminate\Support\Str::uuid()->toString()
                    ]
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'METHOD_NOT_ALLOWED',
                        'message' => 'The HTTP method is not allowed for this endpoint.',
                        'details' => ['allowed_methods' => $e->getHeaders()['Allow'] ?? null]
                    ],
                    'meta' => [
                        'version' => '1.0',
                        'timestamp' => now()->toISOString(),
                        'request_id' => \Illuminate\Support\Str::uuid()->toString()
                    ]
                ], 405);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') && !config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INTERNAL_ERROR',
                        'message' => 'An internal server error occurred.',
                        'details' => null
                    ],
                    'meta' => [
                        'version' => '1.0',
                        'timestamp' => now()->toISOString(),
                        'request_id' => \Illuminate\Support\Str::uuid()->toString()
                    ]
                ], 500);
            }
        });
    })->create();
