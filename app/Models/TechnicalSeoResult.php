<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSeoResult extends Model
{
    use HasFactory;
    protected $fillable = [
        'seo_analysis_id',
        'meta_tags',
        'page_speed',
        'mobile_friendly',
        'ssl_enabled',
        'security_headers',
        'structured_data',
    ];

    protected $casts = [
        'meta_tags' => 'array',
        'mobile_friendly' => 'boolean',
        'ssl_enabled' => 'boolean',
        'security_headers' => 'array',
        'structured_data' => 'array',
    ];

    public function seoAnalysis(): BelongsTo
    {
        return $this->belongsTo(SeoAnalysis::class);
    }

    public function hasGoodPageSpeed(): bool
    {
        return $this->page_speed !== null && $this->page_speed >= 70;
    }

    public function isMobileFriendly(): bool
    {
        return $this->mobile_friendly === true;
    }

    public function hasSSL(): bool
    {
        return $this->ssl_enabled === true;
    }
}
