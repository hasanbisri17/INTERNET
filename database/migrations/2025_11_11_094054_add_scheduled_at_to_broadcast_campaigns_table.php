<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            }
            // Update status enum to include 'scheduled'
            if (Schema::hasColumn('broadcast_campaigns', 'status')) {
                DB::statement("ALTER TABLE broadcast_campaigns MODIFY COLUMN status ENUM('pending', 'scheduled', 'processing', 'completed', 'failed') DEFAULT 'pending'");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
            // Revert status enum
            DB::statement("ALTER TABLE broadcast_campaigns MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'");
        });
    }
};
