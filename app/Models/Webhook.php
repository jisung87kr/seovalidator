<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Webhook extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'url',
        'events',
        'secret',
        'active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_delivery_at' => 'datetime',
        'last_delivery_success' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Available webhook events
     */
    public const AVAILABLE_EVENTS = [
        'analysis.completed',
        'analysis.failed',
        'batch.completed',
        'batch.failed',
    ];

    /**
     * Get the user that owns the webhook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active webhooks.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get webhooks for specific events.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * Calculate success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_deliveries === 0) {
            return 0.0;
        }

        return round(($this->successful_deliveries / $this->total_deliveries) * 100, 2);
    }

    /**
     * Increment delivery statistics.
     */
    public function recordDelivery(bool $success, int $statusCode = null, int $responseTimeMs = null, string $errorMessage = null): void
    {
        $this->increment('total_deliveries');

        if ($success) {
            $this->increment('successful_deliveries');
        } else {
            $this->increment('failed_deliveries');
        }

        $this->update([
            'last_delivery_at' => now(),
            'last_delivery_status_code' => $statusCode,
            'last_delivery_response_time_ms' => $responseTimeMs,
            'last_delivery_success' => $success,
            'last_delivery_error_message' => $errorMessage,
        ]);
    }

    /**
     * Update last triggered timestamp.
     */
    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Check if webhook listens for a specific event.
     */
    public function listensForEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Generate webhook signature for payload verification.
     */
    public function generateSignature(string $payload): string
    {
        if (empty($this->secret)) {
            return '';
        }

        return hash_hmac('sha256', $payload, $this->secret);
    }
}
