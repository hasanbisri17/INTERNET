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
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_settings', 'basic_auth_username')) {
                $table->string('basic_auth_username')->nullable()->after('api_token');
            }
            if (!Schema::hasColumn('whatsapp_settings', 'basic_auth_password')) {
                $table->string('basic_auth_password')->nullable()->after('basic_auth_username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['basic_auth_username', 'basic_auth_password']);
        });
    }
};
