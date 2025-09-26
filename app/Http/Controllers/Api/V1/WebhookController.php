<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\WebhookRequest;
use App\Http\Resources\Api\V1\WebhookResource;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends ApiController
{
    /**
     * Display a listing of the user's webhooks.
     *
     * @group Webhooks
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min($request->query('per_page', 15), 50);

        $webhooks = $user->webhooks()
            ->when($request->query('active'), function ($query, $active) {
                return $query->where('active', $active === 'true');
            })
            ->when($request->query('event'), function ($query, $event) {
                return $query->forEvent($event);
            })
            ->latest()
            ->paginate($perPage);

        return $this->paginated($webhooks->through(fn($webhook) => new WebhookResource($webhook)));
    }

    /**
     * Store a newly created webhook.
     *
     * @group Webhooks
     */
    public function store(WebhookRequest $request): JsonResponse
    {
        try {
            $webhook = $request->user()->webhooks()->create([
                'name' => $request->input('name', 'Webhook ' . Str::random(8)),
                'description' => $request->input('description'),
                'url' => $request->input('url'),
                'events' => $request->input('events'),
                'secret' => $request->input('secret'),
                'active' => $request->input('active', true),
            ]);

            Log::info('Webhook created', [
                'webhook_id' => $webhook->id,
                'user_id' => $request->user()->id,
                'url' => $webhook->url,
                'events' => $webhook->events
            ]);

            return $this->success(
                new WebhookResource($webhook),
                'Webhook created successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('Failed to create webhook', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'request_data' => $request->validated()
            ]);

            return $this->serverError('Failed to create webhook: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified webhook.
     *
     * @group Webhooks
     */
    public function show(Request $request, Webhook $webhook): JsonResponse
    {
        // Ensure webhook belongs to authenticated user
        if ($webhook->user_id !== $request->user()->id) {
            return $this->forbidden('You do not have access to this webhook');
        }

        return $this->success(
            new WebhookResource($webhook),
            'Webhook retrieved successfully'
        );
    }

    /**
     * Update the specified webhook.
     *
     * @group Webhooks
     */
    public function update(WebhookRequest $request, Webhook $webhook): JsonResponse
    {
        // Ensure webhook belongs to authenticated user
        if ($webhook->user_id !== $request->user()->id) {
            return $this->forbidden('You do not have access to this webhook');
        }

        try {
            $webhook->update($request->validated());

            Log::info('Webhook updated', [
                'webhook_id' => $webhook->id,
                'user_id' => $request->user()->id,
                'changes' => $webhook->getChanges()
            ]);

            return $this->success(
                new WebhookResource($webhook->fresh()),
                'Webhook updated successfully'
            );

        } catch (\Exception $e) {
            Log::error('Failed to update webhook', [
                'webhook_id' => $webhook->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to update webhook: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified webhook.
     *
     * @group Webhooks
     */
    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        // Ensure webhook belongs to authenticated user
        if ($webhook->user_id !== $request->user()->id) {
            return $this->forbidden('You do not have access to this webhook');
        }

        try {
            $webhookData = [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'name' => $webhook->name
            ];

            $webhook->delete();

            Log::info('Webhook deleted', [
                'webhook_data' => $webhookData,
                'user_id' => $request->user()->id
            ]);

            return $this->success(null, 'Webhook deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete webhook', [
                'webhook_id' => $webhook->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to delete webhook: ' . $e->getMessage());
        }
    }

    /**
     * Test the specified webhook by sending a test payload.
     *
     * @group Webhooks
     */
    public function test(Request $request, Webhook $webhook): JsonResponse
    {
        // Ensure webhook belongs to authenticated user
        if ($webhook->user_id !== $request->user()->id) {
            return $this->forbidden('You do not have access to this webhook');
        }

        try {
            $testPayload = [
                'event' => 'test',
                'webhook_id' => $webhook->id,
                'timestamp' => now()->toISOString(),
                'data' => [
                    'message' => 'This is a test webhook delivery',
                    'test_id' => Str::uuid()->toString(),
                ],
            ];

            $result = $this->deliverWebhook($webhook, $testPayload);

            if ($result['success']) {
                return $this->success([
                    'test_result' => $result,
                    'webhook' => new WebhookResource($webhook->fresh())
                ], 'Webhook test completed successfully');
            } else {
                return $this->error('Webhook test failed', 422, ['test_result' => $result]);
            }

        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'webhook_id' => $webhook->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Webhook test failed: ' . $e->getMessage());
        }
    }

    /**
     * Deliver a webhook payload to the specified URL
     */
    public function deliverWebhook(Webhook $webhook, array $payload): array
    {
        $startTime = microtime(true);

        try {
            $jsonPayload = json_encode($payload);
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'SEO-Validator-Webhook/1.0',
                'X-SEO-Validator-Event' => $payload['event'] ?? 'unknown',
                'X-SEO-Validator-Delivery' => Str::uuid()->toString(),
            ];

            // Add signature if secret is configured
            if (!empty($webhook->secret)) {
                $signature = $webhook->generateSignature($jsonPayload);
                $headers['X-SEO-Validator-Signature'] = 'sha256=' . $signature;
            }

            // Make HTTP request with timeout
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($webhook->url, $payload);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $success = $response->successful();

            // Record delivery statistics
            $webhook->recordDelivery(
                $success,
                $response->status(),
                $responseTime,
                $success ? null : $response->body()
            );

            $result = [
                'success' => $success,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'response_body' => $response->body(),
                'delivered_at' => now()->toISOString(),
            ];

            if (!$success) {
                $result['error'] = 'HTTP ' . $response->status() . ': ' . $response->reason();
            }

            Log::info('Webhook delivered', [
                'webhook_id' => $webhook->id,
                'success' => $success,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'event' => $payload['event'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Record failed delivery
            $webhook->recordDelivery(
                false,
                null,
                $responseTime,
                $e->getMessage()
            );

            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'delivered_at' => now()->toISOString(),
            ];

            Log::error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'event' => $payload['event'] ?? 'unknown'
            ]);

            return $result;
        }
    }
}