<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
