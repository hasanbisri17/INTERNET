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
        Schema::create('auto_isolir_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->foreignId('isolir_profile_id')->nullable()->constrained('mikrotik_profiles')->onDelete('set null');
            $table->boolean('enabled')->default(true);
            $table->integer('grace_period_days')->default(0)->comment('Days after due date before isolation');
            $table->boolean('auto_restore')->default(true)->comment('Auto restore when payment received');
            $table->boolean('send_notification')->default(true);
            $table->integer('warning_days')->default(3)->comment('Days before due date to send warning');
            $table->string('isolir_profile_name')->nullable()->comment('Profile name for isolated users');
            $table->string('isolir_queue_name')->nullable()->comment('Queue name for isolated users');
            $table->string('isolir_speed')->nullable()->comment('Speed limit for isolated users');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // One config per mikrotik device
            $table->unique('mikrotik_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_isolir_configs');
    }
};

