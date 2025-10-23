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
        Schema::create('mikrotik_netwatch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('netwatch_id')->nullable()->comment('Netwatch ID from MikroTik (.id)');
            $table->string('host')->comment('IP Address or hostname to monitor');
            $table->string('interval')->default('00:01:00')->comment('Check interval (default: 1 minute)');
            $table->string('timeout')->default('1000ms')->comment('Timeout for ping');
            $table->string('status')->nullable()->comment('Current status: up/down/unknown');
            $table->timestamp('since')->nullable()->comment('Since when in current status');
            $table->text('up_script')->nullable()->comment('Script to run when host is up');
            $table->text('down_script')->nullable()->comment('Script to run when host is down');
            $table->string('comment')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->boolean('is_synced')->default(false)->comment('Synced with MikroTik device');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('mikrotik_device_id');
            $table->index('host');
            $table->index('status');
            $table->unique(['mikrotik_device_id', 'netwatch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_netwatch');
    }
};

