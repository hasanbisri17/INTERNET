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
        Schema::table('mikrotik_ip_bindings', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('mikrotik_device_id')->constrained()->onDelete('set null');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mikrotik_ip_bindings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};

