<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    /**
     * Register a new API key
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rate_limit' => 'sometimes|integer|min:1|max:10000',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $apiKey = ApiKey::create([
                'key' => Crypt::encrypt($plainKey = ApiKey::generateKey()),
                'name' => $request->input('name'),
                'rate_limit' => $request->input('rate_limit', 100),
                'expires_at' => $request->input('expires_at'),
                'is_active' => true
            ]);

            $data = [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'api_key' => $plainKey, // Only shown once during creation
                'rate_limit' => $apiKey->rate_limit,
                'expires_at' => $apiKey->expires_at?->toISOString(),
                'created_at' => $apiKey->created_at->toISOString()
            ];

            return $this->success(
                $data,
                'API key created successfully. Please save the key securely as it will not be shown again.',
                201
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to create API key');
        }
    }

    /**
     * Revoke an API key
     */
    public function revoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'api_key_id' => 'required|integer|exists:api_keys,id'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $apiKey = ApiKey::findOrFail($request->input('api_key_id'));
            
            $apiKey->update(['is_active' => false]);

            return $this->success(
                ['api_key_id' => $apiKey->id, 'status' => 'revoked'],
                'API key revoked successfully'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to revoke API key');
        }
    }

    /**
     * Refresh an API key (generate new key)
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'api_key_id' => 'required|integer|exists:api_keys,id'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $apiKey = ApiKey::findOrFail($request->input('api_key_id'));
            
            if (!$apiKey->isActive()) {
                return $this->error(
                    'INVALID_API_KEY',
                    'Cannot refresh inactive or expired API key',
                    null,
                    400
                );
            }

            $newPlainKey = ApiKey::generateKey();
            $apiKey->update([
                'key' => Crypt::encrypt($newPlainKey)
            ]);

            $data = [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'api_key' => $newPlainKey, // Only shown once during refresh
                'rate_limit' => $apiKey->rate_limit,
                'expires_at' => $apiKey->expires_at?->toISOString(),
                'updated_at' => $apiKey->updated_at->toISOString()
            ];

            return $this->success(
                $data,
                'API key refreshed successfully. Please save the new key securely.'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to refresh API key');
        }
    }
}