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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('connection_type', ['pppoe', 'static'])->default('pppoe')->after('internet_package_id')->comment('Jenis koneksi: PPPOE atau STATIC');
            $table->string('pppoe_username')->nullable()->after('connection_type')->comment('Username PPPOE (auto-generated)');
            $table->string('pppoe_password')->nullable()->after('pppoe_username')->comment('Password PPPOE (auto-generated)');
            $table->string('customer_id')->nullable()->unique()->after('pppoe_password')->comment('ID Pelanggan untuk STATIC (auto-generated)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['connection_type', 'pppoe_username', 'pppoe_password', 'customer_id']);
        });
    }
};
