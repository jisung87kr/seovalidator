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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('url', 2048);
            $table->json('events'); // Array of event types to listen for
            $table->string('secret')->nullable(); // For webhook signature verification
            $table->boolean('active')->default(true);

            // Delivery statistics
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);

            // Last delivery information
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_delivery_at')->nullable();
            $table->integer('last_delivery_status_code')->nullable();
            $table->integer('last_delivery_response_time_ms')->nullable();
            $table->boolean('last_delivery_success')->nullable();
            $table->text('last_delivery_error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'active']);
            $table->index('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
