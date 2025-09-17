<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class CrawlResult
{
    public function __construct(
        public string $url,
        public ?string $title,
        public ?string $metaDescription,
        public ?string $htmlContent,
        public array $images,
        public array $internalLinks,
        public array $externalLinks,
        public array $metaTags,
        public ?float $responseTime,
        public ?int $statusCode,
        public ?string $errorMessage,
        public Carbon $crawledAt,
        public ?array $headings = null,
        public ?string $canonicalUrl = null,
        public ?array $openGraphTags = null,
        public ?array $twitterTags = null
    ) {}

    /**
     * Create a successful crawl result
     */
    public static function success(
        string $url,
        string $htmlContent,
        array $extractedData,
        float $responseTime,
        int $statusCode
    ): self {
        return new self(
            url: $url,
            title: $extractedData['title'] ?? null,
            metaDescription: $extractedData['meta_description'] ?? null,
            htmlContent: $htmlContent,
            images: $extractedData['images'] ?? [],
            internalLinks: $extractedData['internal_links'] ?? [],
            externalLinks: $extractedData['external_links'] ?? [],
            metaTags: $extractedData['meta_tags'] ?? [],
            responseTime: $responseTime,
            statusCode: $statusCode,
            errorMessage: null,
            crawledAt: Carbon::now(),
            headings: $extractedData['headings'] ?? null,
            canonicalUrl: $extractedData['canonical_url'] ?? null,
            openGraphTags: $extractedData['og_tags'] ?? null,
            twitterTags: $extractedData['twitter_tags'] ?? null
        );
    }

    /**
     * Create a failed crawl result
     */
    public static function failure(
        string $url,
        string $errorMessage,
        ?int $statusCode = null,
        ?float $responseTime = null
    ): self {
        return new self(
            url: $url,
            title: null,
            metaDescription: null,
            htmlContent: null,
            images: [],
            internalLinks: [],
            externalLinks: [],
            metaTags: [],
            responseTime: $responseTime,
            statusCode: $statusCode,
            errorMessage: $errorMessage,
            crawledAt: Carbon::now()
        );
    }

    /**
     * Check if crawl was successful
     */
    public function isSuccessful(): bool
    {
        return $this->errorMessage === null && $this->htmlContent !== null;
    }

    /**
     * Get total links count
     */
    public function getTotalLinksCount(): int
    {
        return count($this->internalLinks) + count($this->externalLinks);
    }

    /**
     * Get images without alt text
     */
    public function getImagesWithoutAlt(): array
    {
        return array_filter($this->images, fn($img) => empty($img['alt']));
    }
}