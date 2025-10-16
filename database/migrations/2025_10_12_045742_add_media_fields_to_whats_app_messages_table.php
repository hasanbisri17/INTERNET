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
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('message');
            $table->enum('media_type', ['image', 'document', null])->nullable()->after('media_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};
