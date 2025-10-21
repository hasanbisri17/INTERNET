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
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('billing_month')->nullable()->after('invoice_number')->comment('Bulan tagihan (1-12)');
            $table->integer('billing_year')->nullable()->after('billing_month')->comment('Tahun tagihan (misal: 2025)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['billing_month', 'billing_year']);
        });
    }
};
