<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSeoResult extends Model
{
    use HasFactory;
    protected $fillable = [
        'seo_analysis_id',
        'keyword_density',
        'readability_score',
        'h_tags',
        'word_count',
        'internal_links',
        'external_links',
        'image_analysis',
    ];

    protected $casts = [
        'keyword_density' => 'array',
        'h_tags' => 'array',
        'internal_links' => 'array',
        'external_links' => 'array',
        'image_analysis' => 'array',
    ];

    public function seoAnalysis(): BelongsTo
    {
        return $this->belongsTo(SeoAnalysis::class);
    }

    public function hasGoodReadability(): bool
    {
        return $this->readability_score !== null && $this->readability_score >= 60;
    }

    public function hasAdequateWordCount(): bool
    {
        return $this->word_count !== null && $this->word_count >= 300;
    }

    public function getInternalLinksCount(): int
    {
        return is_array($this->internal_links) ? count($this->internal_links) : 0;
    }

    public function getExternalLinksCount(): int
    {
        return is_array($this->external_links) ? count($this->external_links) : 0;
    }
}
