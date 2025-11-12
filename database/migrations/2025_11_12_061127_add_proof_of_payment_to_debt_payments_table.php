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
        Schema::table('debt_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('debt_payments', 'proof_of_payment')) {
                $table->string('proof_of_payment')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropColumn('proof_of_payment');
        });
    }
};
