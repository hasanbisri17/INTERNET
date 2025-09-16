<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('canceled_at')->nullable()->after('payment_date');
            $table->foreignId('canceled_by')->nullable()->after('canceled_at')->constrained('users')->nullOnDelete();
            $table->text('canceled_reason')->nullable()->after('canceled_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['canceled_by']);
            $table->dropColumn(['canceled_at', 'canceled_by', 'canceled_reason']);
        });
    }
};