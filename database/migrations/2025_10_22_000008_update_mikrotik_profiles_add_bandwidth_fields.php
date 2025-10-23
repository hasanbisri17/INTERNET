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
        Schema::table('mikrotik_profiles', function (Blueprint $table) {
            // Max Limit
            $table->string('max_limit_upload')->nullable()->after('rate_limit')->comment('Max upload speed (e.g., 10M)');
            $table->string('max_limit_download')->nullable()->after('max_limit_upload')->comment('Max download speed (e.g., 10M)');
            
            // Burst Limit
            $table->string('burst_limit_upload')->nullable()->after('max_limit_download')->comment('Burst upload speed (e.g., 20M)');
            $table->string('burst_limit_download')->nullable()->after('burst_limit_upload')->comment('Burst download speed (e.g., 20M)');
            
            // Burst Threshold
            $table->string('burst_threshold_upload')->nullable()->after('burst_limit_download')->comment('Burst threshold upload (e.g., 8M)');
            $table->string('burst_threshold_download')->nullable()->after('burst_threshold_upload')->comment('Burst threshold download (e.g., 8M)');
            
            // Burst Time
            $table->string('burst_time_upload')->nullable()->after('burst_threshold_download')->comment('Burst time upload in seconds (e.g., 8s)');
            $table->string('burst_time_download')->nullable()->after('burst_time_upload')->comment('Burst time download in seconds (e.g., 8s)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mikrotik_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'max_limit_upload',
                'max_limit_download',
                'burst_limit_upload',
                'burst_limit_download',
                'burst_threshold_upload',
                'burst_threshold_download',
                'burst_time_upload',
                'burst_time_download',
            ]);
        });
    }
};

