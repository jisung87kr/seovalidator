<?php

namespace App\DTOs;

readonly class ContentStructureResult
{
    public function __construct(
        public array $headingStructure,
        public array $linkAnalysis,
        public array $imageAnalysis,
        public array $contentHierarchy,
        public int $contentLength,
        public float $contentDepthScore,
        public array $structureIssues,
        public array $suggestions
    ) {}

    public function toArray(): array
    {
        return [
            'heading_structure' => $this->headingStructure,
            'link_analysis' => $this->linkAnalysis,
            'image_analysis' => $this->imageAnalysis,
            'content_hierarchy' => $this->contentHierarchy,
            'content_length' => $this->contentLength,
            'content_depth_score' => $this->contentDepthScore,
            'structure_issues' => $this->structureIssues,
            'suggestions' => $this->suggestions,
        ];
    }

    public function hasGoodHeadingStructure(): bool
    {
        return count($this->structureIssues) === 0;
    }

    public function hasAdequateContentLength(): bool
    {
        return $this->contentLength >= 300;
    }

    public function getInternalLinksCount(): int
    {
        return $this->linkAnalysis['internal_count'] ?? 0;
    }

    public function getExternalLinksCount(): int
    {
        return $this->linkAnalysis['external_count'] ?? 0;
    }

    public function getImagesWithoutAltCount(): int
    {
        return $this->imageAnalysis['without_alt_count'] ?? 0;
    }
}