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
        Schema::create('url_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_analysis_id')->constrained()->onDelete('cascade');
            $table->string('url', 2048);
            $table->string('title', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->smallInteger('status_code')->nullable();
            $table->timestamps();
            
            $table->index('seo_analysis_id');
            $table->index('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_analyses');
    }
};
