<?php

namespace Tests\Unit;

use App\Models\SeoAnalysis;
use App\Models\TechnicalSeoResult;
use App\Models\ContentSeoResult;
use App\Models\UrlAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_analysis_can_be_created(): void
    {
        $analysis = SeoAnalysis::factory()->create([
            'url' => 'https://example.com',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('seo_analyses', [
            'url' => 'https://example.com',
            'status' => 'pending',
        ]);
    }

    public function test_seo_analysis_status_helpers(): void
    {
        $pending = SeoAnalysis::factory()->create(['status' => 'pending']);
        $running = SeoAnalysis::factory()->create(['status' => 'running']);
        $completed = SeoAnalysis::factory()->create(['status' => 'completed']);
        $failed = SeoAnalysis::factory()->create(['status' => 'failed']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isCompleted());

        $this->assertTrue($running->isRunning());
        $this->assertFalse($running->isPending());

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isRunning());

        $this->assertTrue($failed->hasFailed());
        $this->assertFalse($failed->isCompleted());
    }

    public function test_seo_analysis_has_relationships(): void
    {
        $analysis = SeoAnalysis::factory()->create();
        
        $urlAnalysis = UrlAnalysis::factory()->create([
            'seo_analysis_id' => $analysis->id,
        ]);
        
        $techResult = TechnicalSeoResult::factory()->create([
            'seo_analysis_id' => $analysis->id,
        ]);
        
        $contentResult = ContentSeoResult::factory()->create([
            'seo_analysis_id' => $analysis->id,
        ]);

        $this->assertInstanceOf(UrlAnalysis::class, $analysis->urlAnalyses->first());
        $this->assertInstanceOf(TechnicalSeoResult::class, $analysis->technicalSeoResult);
        $this->assertInstanceOf(ContentSeoResult::class, $analysis->contentSeoResult);
    }
}
