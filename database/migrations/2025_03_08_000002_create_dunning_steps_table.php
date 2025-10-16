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
        Schema::create('dunning_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('step_name');
            $table->integer('days_after_due');
            $table->string('action_type'); // notification, penalty, suspend
            $table->json('action_config')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->string('status')->default('pending'); // pending, executed, skipped
            $table->timestamps();
            
            // Index for performance
            $table->index(['payment_id', 'status']);
            $table->index(['status', 'days_after_due']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dunning_steps');
    }
};