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
        Schema::create('technical_seo_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_analysis_id')->constrained()->onDelete('cascade');
            $table->json('meta_tags')->nullable()->comment('Meta tags analysis results');
            $table->smallInteger('page_speed')->nullable()->comment('Page speed score 0-100');
            $table->boolean('mobile_friendly')->nullable();
            $table->boolean('ssl_enabled')->nullable();
            $table->json('security_headers')->nullable();
            $table->json('structured_data')->nullable();
            $table->timestamps();
            
            $table->index('seo_analysis_id');
            $table->index('page_speed');
            $table->index('mobile_friendly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_seo_results');
    }
};
