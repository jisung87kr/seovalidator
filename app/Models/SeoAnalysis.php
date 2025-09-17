<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeoAnalysis extends Model
{
    use HasFactory;
    protected $fillable = [
        'url',
        'status',
        'score',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function urlAnalyses(): HasMany
    {
        return $this->hasMany(UrlAnalysis::class);
    }

    public function technicalSeoResult(): HasOne
    {
        return $this->hasOne(TechnicalSeoResult::class);
    }

    public function contentSeoResult(): HasOne
    {
        return $this->hasOne(ContentSeoResult::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
