<?php

namespace App\Contracts;

use App\DTOs\CrawlResult;

interface CrawlerInterface
{
    /**
     * Crawl a URL and extract SEO-relevant data
     */
    public function crawl(string $url): CrawlResult;

    /**
     * Validate if URL is crawlable
     */
    public function validateUrl(string $url): bool;

    /**
     * Set custom User-Agent
     */
    public function setUserAgent(string $userAgent): self;

    /**
     * Set timeout for crawling
     */
    public function setTimeout(int $seconds): self;
}