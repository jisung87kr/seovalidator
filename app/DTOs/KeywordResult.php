<?php

namespace App\DTOs;

readonly class KeywordResult
{
    public function __construct(
        public array $densityMap,
        public array $prominentKeywords,
        public array $semanticKeywords,
        public bool $hasKeywordStuffing,
        public array $longTailKeywords,
        public array $keywordDistribution,
        public float $overallDensity,
        public array $suggestions
    ) {}

    public function toArray(): array
    {
        return [
            'density_map' => $this->densityMap,
            'prominent_keywords' => $this->prominentKeywords,
            'semantic_keywords' => $this->semanticKeywords,
            'has_keyword_stuffing' => $this->hasKeywordStuffing,
            'long_tail_keywords' => $this->longTailKeywords,
            'keyword_distribution' => $this->keywordDistribution,
            'overall_density' => $this->overallDensity,
            'suggestions' => $this->suggestions,
        ];
    }

    public function getTopKeywords(int $limit = 10): array
    {
        arsort($this->densityMap);
        return array_slice($this->densityMap, 0, $limit, true);
    }

    public function hasGoodKeywordDensity(): bool
    {
        return $this->overallDensity >= 0.5 && $this->overallDensity <= 3.0;
    }
}