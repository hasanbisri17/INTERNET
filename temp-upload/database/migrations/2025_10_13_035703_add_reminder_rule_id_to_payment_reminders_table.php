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
        Schema::table('payment_reminders', function (Blueprint $table) {
            // Add reminder_rule_id column
            $table->foreignId('reminder_rule_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('payment_reminder_rules')
                ->nullOnDelete();

            // Update reminder_type to be more flexible (no longer enum, just string)
            $table->string('reminder_type')->nullable()->change();
            
            // Add index
            $table->index('reminder_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->dropForeign(['reminder_rule_id']);
            $table->dropIndex(['reminder_rule_id']);
            $table->dropColumn('reminder_rule_id');
            
            // Revert reminder_type back to enum (optional, can be skipped)
        });
    }
};
