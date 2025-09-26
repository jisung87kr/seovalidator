<?php

namespace App\Livewire;

use Livewire\Component;

class RecentAnalysesList extends Component
{
    public $analyses = [];

    public function mount()
    {
        $this->loadRecentAnalyses();
    }

    public function loadRecentAnalyses()
    {
        // For now, use mock data since we don't have database connection
        $this->analyses = $this->getMockAnalyses();
    }

    private function getMockAnalyses()
    {
        return [
            [
                'id' => 1,
                'url' => 'https://example.com',
                'overall_score' => 78,
                'status' => 'completed',
                'analyzed_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'url' => 'https://test-site.com',
                'overall_score' => 85,
                'status' => 'completed',
                'analyzed_at' => now()->subDays(1)->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'url' => 'https://demo.website.org',
                'overall_score' => 62,
                'status' => 'completed',
                'analyzed_at' => now()->subDays(3)->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'url' => 'https://another-site.net',
                'overall_score' => null,
                'status' => 'processing',
                'analyzed_at' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function getScoreColor($score)
    {
        if (!$score) return 'gray';
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'yellow';
        return 'red';
    }

    public function viewAnalysis($analysisId)
    {
        return redirect()->route('analysis.show', $analysisId);
    }

    public function render()
    {
        return view('livewire.recent-analyses-list');
    }
}
