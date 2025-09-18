<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseApiController extends Controller
{
    /**
     * Return a successful response
     */
    protected function success(
        mixed $data = null, 
        string $message = 'Success', 
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $this->getResponseMeta()
        ], $statusCode);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $code,
        string $message,
        mixed $details = null,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => $this->getResponseMeta()
        ], $statusCode);
    }

    /**
     * Return validation error response
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error(
            'VALIDATION_ERROR',
            'The given data was invalid.',
            $errors,
            422
        );
    }

    /**
     * Return not found error response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error(
            'NOT_FOUND',
            $message,
            null,
            404
        );
    }

    /**
     * Return unauthorized error response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error(
            'UNAUTHORIZED',
            $message,
            null,
            401
        );
    }

    /**
     * Return forbidden error response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error(
            'FORBIDDEN',
            $message,
            null,
            403
        );
    }

    /**
     * Return server error response
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error(
            'INTERNAL_ERROR',
            $message,
            null,
            500
        );
    }

    /**
     * Get response metadata
     */
    private function getResponseMeta(): array
    {
        return [
            'version' => '1.0',
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID') ?? Str::uuid()->toString()
        ];
    }
}