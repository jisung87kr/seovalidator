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
    public $analysisId = null;

    public function mount()
    {
        $this->resetForm();
    }

    /**
     * Check analysis status (called by polling)
     */
    public function checkStatus()
    {
        if (!$this->analysisId) {
            return;
        }

        $analysis = SeoAnalysis::find($this->analysisId);

        if (!$analysis) {
            $this->resetForm();
            return;
        }

        $this->currentAnalysis = $analysis;

        if ($analysis->status === 'completed') {
            $this->isAnalyzing = false;
            return redirect()->route('analysis.show', $analysis->id);
        }

        if ($analysis->status === 'failed') {
            $this->isAnalyzing = false;
            $this->addError('url', $analysis->error_message ?? __('dashboard.analysis_failed'));
        }
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

            // Create analysis record and dispatch job
            $analysis = SeoAnalysis::create([
                'user_id' => auth()->id(),
                'url' => $this->url,
                'status' => 'pending'
            ]);

            AnalyzeUrl::dispatch($analysis->url, auth()->id());
            $this->currentAnalysis = $analysis;
            $this->analysisId = $analysis->id;

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
        $this->analysisId = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.url-analysis-form');
    }
}
