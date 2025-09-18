<?php

namespace App\DTOs;

class TextProcessorResult
{
    public int $wordCount;
    public int $characterCount;
    public int $sentenceCount;
    public int $paragraphCount;
    public string $detectedLanguage;
    public array $languageMetrics;
    public array $wordFrequency;
    public float $textDensity;
    public string $cleanText;
    public ?string $cleanedText = null; // Alias for cleanText
    public ?string $normalizedText = null;
    public ?array $sentenceStructure = null;

    public function __construct(
        int $wordCount = 0,
        int $characterCount = 0,
        int $sentenceCount = 0,
        int $paragraphCount = 0,
        string $detectedLanguage = 'unknown',
        array $languageMetrics = [],
        array $wordFrequency = [],
        float $textDensity = 0.0,
        string $cleanText = '',
        ?string $normalizedText = null,
        ?array $sentenceStructure = null
    ) {
        $this->wordCount = $wordCount;
        $this->characterCount = $characterCount;
        $this->sentenceCount = $sentenceCount;
        $this->paragraphCount = $paragraphCount;
        $this->detectedLanguage = $detectedLanguage;
        $this->languageMetrics = $languageMetrics;
        $this->wordFrequency = $wordFrequency;
        $this->textDensity = $textDensity;
        $this->cleanText = $cleanText;
        $this->cleanedText = $cleanText; // Alias
        $this->normalizedText = $normalizedText ?? $cleanText;
        $this->sentenceStructure = $sentenceStructure;
    }
}