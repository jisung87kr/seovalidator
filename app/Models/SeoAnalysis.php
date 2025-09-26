<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeoAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'title',
        'status',
        'overall_score',
        'technical_score',
        'content_score',
        'performance_score',
        'accessibility_score',
        'analysis_data',
        'error_message',
        'analyzed_at',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'analyzed_at' => 'datetime',
        'overall_score' => 'decimal:2',
        'technical_score' => 'decimal:2',
        'content_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'accessibility_score' => 'decimal:2',
    ];

    /**
     * Get the user that owns the analysis.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get completed analyses.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed analyses.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get pending analyses.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
