<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_seo_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_analysis_id')->constrained()->onDelete('cascade');
            $table->json('keyword_density')->nullable()->comment('Keyword density analysis');
            $table->smallInteger('readability_score')->nullable()->comment('Readability score 0-100');
            $table->json('h_tags')->nullable()->comment('Heading tags structure analysis');
            $table->smallInteger('word_count')->nullable();
            $table->json('internal_links')->nullable();
            $table->json('external_links')->nullable();
            $table->json('image_analysis')->nullable();
            $table->timestamps();
            
            $table->index('seo_analysis_id');
            $table->index('readability_score');
            $table->index('word_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_seo_results');
    }
};
