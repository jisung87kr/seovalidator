<?php

namespace App\DTOs;

readonly class ReadabilityResult
{
    public function __construct(
        public float $fleschKincaidScore,
        public float $gunningFogIndex,
        public float $smogIndex,
        public float $averageSentenceLength,
        public float $averageWordsPerSentence,
        public int $totalSyllables,
        public int $complexWords,
        public int $totalWords,
        public int $totalSentences,
        public string $readingLevel,
        public array $suggestions
    ) {}

    public function toArray(): array
    {
        return [
            'flesch_kincaid_score' => $this->fleschKincaidScore,
            'gunning_fog_index' => $this->gunningFogIndex,
            'smog_index' => $this->smogIndex,
            'average_sentence_length' => $this->averageSentenceLength,
            'average_words_per_sentence' => $this->averageWordsPerSentence,
            'total_syllables' => $this->totalSyllables,
            'complex_words' => $this->complexWords,
            'total_words' => $this->totalWords,
            'total_sentences' => $this->totalSentences,
            'reading_level' => $this->readingLevel,
            'suggestions' => $this->suggestions,
        ];
    }

    public function isEasyToRead(): bool
    {
        return $this->fleschKincaidScore >= 60;
    }

    public function getReadingGrade(): string
    {
        if ($this->fleschKincaidScore >= 90) {
            return '5th Grade';
        } elseif ($this->fleschKincaidScore >= 80) {
            return '6th Grade';
        } elseif ($this->fleschKincaidScore >= 70) {
            return '7th Grade';
        } elseif ($this->fleschKincaidScore >= 60) {
            return '8th & 9th Grade';
        } elseif ($this->fleschKincaidScore >= 50) {
            return '10th to 12th Grade';
        } elseif ($this->fleschKincaidScore >= 30) {
            return 'College Level';
        } else {
            return 'Graduate Level';
        }
    }
}