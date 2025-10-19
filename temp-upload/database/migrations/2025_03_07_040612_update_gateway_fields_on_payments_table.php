<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('payment_method_id');
            $table->string('gateway_ref')->nullable()->after('gateway');
            $table->json('payload')->nullable()->after('notes');
        });

        // Update enum values for status without requiring doctrine/dbal
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','overdue','failed','expired','refunded','canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_ref', 'payload']);
        });

        // Revert enum values
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending'");
    }
};