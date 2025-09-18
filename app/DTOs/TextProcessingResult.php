<?php

namespace App\DTOs;

readonly class TextProcessingResult
{
    public function __construct(
        public string $cleanedText,
        public string $normalizedText,
        public string $detectedLanguage,
        public int $wordCount,
        public int $characterCount,
        public int $sentenceCount,
        public int $paragraphCount,
        public float $textDensity,
        public array $wordFrequency,
        public array $sentenceStructure,
        public array $languageMetrics
    ) {}

    public function toArray(): array
    {
        return [
            'cleaned_text' => $this->cleanedText,
            'normalized_text' => $this->normalizedText,
            'detected_language' => $this->detectedLanguage,
            'word_count' => $this->wordCount,
            'character_count' => $this->characterCount,
            'sentence_count' => $this->sentenceCount,
            'paragraph_count' => $this->paragraphCount,
            'text_density' => $this->textDensity,
            'word_frequency' => $this->wordFrequency,
            'sentence_structure' => $this->sentenceStructure,
            'language_metrics' => $this->languageMetrics,
        ];
    }

    public function getAverageWordsPerSentence(): float
    {
        return $this->sentenceCount > 0 ? $this->wordCount / $this->sentenceCount : 0;
    }

    public function getAverageWordsPerParagraph(): float
    {
        return $this->paragraphCount > 0 ? $this->wordCount / $this->paragraphCount : 0;
    }

    public function hasAdequateLength(): bool
    {
        return $this->wordCount >= 300;
    }
}