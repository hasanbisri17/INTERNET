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
        // Drop dunning related tables
        Schema::dropIfExists('dunning_suspensions');
        Schema::dropIfExists('dunning_steps');
        Schema::dropIfExists('dunning_schedules');
        Schema::dropIfExists('dunning_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate dunning_configs table
        Schema::create('dunning_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('n8n_webhook_url')->nullable();
            $table->string('n8n_api_key')->nullable();
            $table->boolean('n8n_enabled')->default(false);
            $table->boolean('test_mode')->default(false);
            $table->string('test_customer_id')->nullable();
            $table->timestamps();
        });

        // Recreate dunning_schedules table
        Schema::create('dunning_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_config_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('current_step')->default(0);
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('next_process_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Recreate dunning_steps table
        Schema::create('dunning_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_config_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order');
            $table->integer('days_after_due');
            $table->string('action_type'); // email, sms, suspend, etc
            $table->json('action_data')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Recreate dunning_suspensions table
        Schema::create('dunning_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('n8n_webhook_url')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->integer('webhook_response_code')->nullable();
            $table->text('webhook_response')->nullable();
            $table->enum('status', ['pending', 'triggered', 'failed'])->default('pending');
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
        });
    }
};

