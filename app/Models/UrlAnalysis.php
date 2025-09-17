<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrlAnalysis extends Model
{
    use HasFactory;
    protected $fillable = [
        'seo_analysis_id',
        'url',
        'title',
        'meta_description',
        'status_code',
    ];

    public function seoAnalysis(): BelongsTo
    {
        return $this->belongsTo(SeoAnalysis::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function isRedirect(): bool
    {
        return $this->status_code >= 300 && $this->status_code < 400;
    }

    public function hasError(): bool
    {
        return $this->status_code >= 400;
    }
}
