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
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_transactions', 'debt_id')) {
                $table->foreignId('debt_id')->nullable()->after('payment_id')->constrained('debts')->nullOnDelete();
            }
            if (!Schema::hasColumn('cash_transactions', 'receivable_id')) {
                $table->foreignId('receivable_id')->nullable()->after('debt_id')->constrained('receivables')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['debt_id']);
            $table->dropForeign(['receivable_id']);
            $table->dropColumn(['debt_id', 'receivable_id']);
        });
    }
};
