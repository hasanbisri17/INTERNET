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
        Schema::create('mikrotik_monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['online', 'offline', 'error'])->default('offline');
            $table->string('uptime')->nullable();
            $table->string('cpu_load')->nullable();
            $table->string('free_memory')->nullable();
            $table->string('total_memory')->nullable();
            $table->string('free_hdd')->nullable();
            $table->string('total_hdd')->nullable();
            $table->integer('active_users')->default(0);
            $table->string('version')->nullable();
            $table->string('board_name')->nullable();
            $table->text('error_message')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['mikrotik_device_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_monitoring_logs');
    }
};

