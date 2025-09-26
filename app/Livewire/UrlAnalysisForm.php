<?php

namespace App\Livewire;

use App\Jobs\AnalyzeUrl;
use App\Models\SeoAnalysis;
use Livewire\Component;
use Livewire\Attributes\Validate;

class UrlAnalysisForm extends Component
{
    #[Validate('required|url|max:2048')]
    public $url = '';

    public $isAnalyzing = false;
    public $currentAnalysis = null;
    public $errors = [];

    public function mount()
    {
        $this->resetForm();
    }

    public function updatedUrl()
    {
        $this->validateUrl();
    }

    public function validateUrl()
    {
        $this->resetErrorBag();

        if (empty($this->url)) {
            return;
        }

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->addError('url', 'Please enter a valid URL.');
            return;
        }

        if (!preg_match('/^https?:\/\//i', $this->url)) {
            $this->addError('url', 'URL must start with http:// or https://');
            return;
        }

        // Clear any existing errors if validation passes
        $this->resetErrorBag('url');
    }

    public function analyzeUrl()
    {
        $this->validateUrl();

        if (!empty($this->getErrorBag()->toArray())) {
            return;
        }

        try {
            $this->isAnalyzing = true;

            // For now, create a mock analysis since we don't have database connection
            $this->currentAnalysis = [
                'id' => uniqid(),
                'url' => $this->url,
                'status' => 'processing',
                'created_at' => now()->format('Y-m-d H:i:s')
            ];

            // In a real implementation, this would be:
            // $analysis = SeoAnalysis::create([
            //     'user_id' => auth()->id(),
            //     'url' => $this->url,
            //     'status' => 'pending'
            // ]);
            //
            // AnalyzeUrl::dispatch($analysis);
            // $this->currentAnalysis = $analysis;

            // Simulate processing time
            $this->dispatch('analysis-started', $this->currentAnalysis);

            session()->flash('success', 'Analysis started for: ' . $this->url);

        } catch (\Exception $e) {
            $this->addError('url', 'Failed to start analysis: ' . $e->getMessage());
            $this->isAnalyzing = false;
        }
    }

    public function resetForm()
    {
        $this->url = '';
        $this->isAnalyzing = false;
        $this->currentAnalysis = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.url-analysis-form');
    }
}
