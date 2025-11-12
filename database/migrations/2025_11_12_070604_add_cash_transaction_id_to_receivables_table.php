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
        Schema::table('receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('receivables', 'cash_transaction_id')) {
                $table->foreignId('cash_transaction_id')->nullable()->after('created_by')->constrained('cash_transactions')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['cash_transaction_id']);
            $table->dropColumn('cash_transaction_id');
        });
    }
};
