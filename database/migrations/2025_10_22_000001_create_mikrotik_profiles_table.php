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
        Schema::create('mikrotik_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('profile_name')->comment('Profile name in Mikrotik');
            $table->string('profile_id')->nullable()->comment('Profile ID from Mikrotik');
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->string('rate_limit')->nullable()->comment('Format: upload/download');
            $table->string('shared_users')->default('1');
            $table->string('parent_queue')->nullable();
            $table->boolean('only_one')->default(false);
            $table->text('comment')->nullable();
            $table->json('additional_config')->nullable();
            $table->boolean('is_synced')->default(false)->comment('Synced with Mikrotik device');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['mikrotik_device_id', 'profile_name']);
            $table->index('profile_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_profiles');
    }
};

