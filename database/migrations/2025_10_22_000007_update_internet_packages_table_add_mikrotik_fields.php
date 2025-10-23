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
        Schema::table('internet_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('internet_packages', 'mikrotik_profile_id')) {
                $table->foreignId('mikrotik_profile_id')->nullable()->after('description')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('internet_packages', 'download_speed')) {
                $table->string('download_speed')->nullable()->after('speed')->comment('Download speed in bps/kbps/Mbps');
            }
            if (!Schema::hasColumn('internet_packages', 'upload_speed')) {
                $table->string('upload_speed')->nullable()->after('download_speed')->comment('Upload speed in bps/kbps/Mbps');
            }
            if (!Schema::hasColumn('internet_packages', 'burst_limit')) {
                $table->string('burst_limit')->nullable()->after('upload_speed')->comment('Burst limit upload/download');
            }
            if (!Schema::hasColumn('internet_packages', 'burst_threshold')) {
                $table->string('burst_threshold')->nullable()->after('burst_limit')->comment('Burst threshold upload/download');
            }
            if (!Schema::hasColumn('internet_packages', 'burst_time')) {
                $table->string('burst_time')->nullable()->after('burst_threshold')->comment('Burst time in seconds');
            }
            if (!Schema::hasColumn('internet_packages', 'pool_name')) {
                $table->string('pool_name')->nullable()->after('burst_time')->comment('IP Pool name for this package');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropForeign(['mikrotik_profile_id']);
            $table->dropColumn([
                'mikrotik_profile_id',
                'download_speed',
                'upload_speed',
                'burst_limit',
                'burst_threshold',
                'burst_time',
                'pool_name',
            ]);
        });
    }
};

