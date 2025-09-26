<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'events' => $this->events,
            'active' => $this->active,
            'secret_configured' => !empty($this->secret),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'last_triggered_at' => $this->last_triggered_at?->toISOString(),
            'delivery_stats' => [
                'total_deliveries' => $this->total_deliveries ?? 0,
                'successful_deliveries' => $this->successful_deliveries ?? 0,
                'failed_deliveries' => $this->failed_deliveries ?? 0,
                'success_rate' => $this->when(
                    ($this->total_deliveries ?? 0) > 0,
                    round((($this->successful_deliveries ?? 0) / ($this->total_deliveries ?? 1)) * 100, 2)
                ),
            ],
            'last_delivery' => $this->when(
                isset($this->last_delivery_at),
                [
                    'delivered_at' => $this->last_delivery_at?->toISOString(),
                    'status_code' => $this->last_delivery_status_code,
                    'response_time_ms' => $this->last_delivery_response_time_ms,
                    'success' => $this->last_delivery_success,
                    'error_message' => $this->when(
                        !$this->last_delivery_success,
                        $this->last_delivery_error_message
                    ),
                ]
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.v1.webhooks.show', ['webhook' => $this->id]),
                'update' => route('api.v1.webhooks.update', ['webhook' => $this->id]),
                'delete' => route('api.v1.webhooks.destroy', ['webhook' => $this->id]),
                'test' => route('api.v1.webhooks.test', ['webhook' => $this->id]),
            ],
        ];
    }
}