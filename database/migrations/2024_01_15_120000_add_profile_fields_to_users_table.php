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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->nullable()->after('email');
            $table->text('bio')->nullable()->after('company');
            $table->boolean('email_notifications')->default(true)->after('bio');
            $table->boolean('weekly_reports')->default(true)->after('email_notifications');
            $table->boolean('marketing_emails')->default(false)->after('weekly_reports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company',
                'bio',
                'email_notifications',
                'weekly_reports',
                'marketing_emails'
            ]);
        });
    }
};