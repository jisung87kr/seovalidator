<?php

namespace App\Livewire;

use Livewire\Component;

class SeoScoreDisplay extends Component
{
    public $analysis;
    public $showDetails = false;

    public function mount($analysis = null)
    {
        // For demo purposes, create mock analysis data
        $this->analysis = $analysis ?: $this->getMockAnalysis();
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    private function getMockAnalysis()
    {
        return [
            'id' => 'demo-analysis',
            'url' => 'https://example.com',
            'overall_score' => 78,
            'technical_score' => 85,
            'content_score' => 72,
            'performance_score' => 68,
            'accessibility_score' => 83,
            'status' => 'completed',
            'analyzed_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'analysis_data' => [
                'technical' => [
                    'meta_tags' => ['score' => 90, 'issues' => 2],
                    'headings' => ['score' => 85, 'issues' => 1],
                    'images' => ['score' => 75, 'issues' => 5],
                    'links' => ['score' => 80, 'issues' => 3],
                ],
                'content' => [
                    'word_count' => 850,
                    'readability' => 72,
                    'keyword_density' => 2.5,
                ],
                'performance' => [
                    'page_size' => '2.4MB',
                    'load_time' => 3.2,
                    'requests' => 45,
                ],
                'accessibility' => [
                    'alt_tags' => 85,
                    'contrast' => 90,
                    'navigation' => 75,
                ],
            ]
        ];
    }

    public function getScoreColor($score)
    {
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'yellow';
        return 'red';
    }

    public function getScoreGrade($score)
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    public function render()
    {
        return view('livewire.seo-score-display');
    }
}
