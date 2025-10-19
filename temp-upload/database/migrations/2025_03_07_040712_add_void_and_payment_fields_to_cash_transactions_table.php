<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('description');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
            $table->foreignId('payment_id')->nullable()->after('category_id')->constrained('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['payment_id']);
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason', 'payment_id']);
        });
    }
};