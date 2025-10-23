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
        Schema::create('mikrotik_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('queue_id')->nullable()->comment('Queue ID from Mikrotik');
            $table->string('name');
            $table->string('target')->nullable()->comment('IP address or interface');
            $table->enum('type', ['simple', 'tree'])->default('simple');
            $table->string('max_limit')->nullable()->comment('Format: upload/download');
            $table->string('limit_at')->nullable()->comment('Format: upload/download');
            $table->string('burst_limit')->nullable()->comment('Format: upload/download');
            $table->string('burst_threshold')->nullable()->comment('Format: upload/download');
            $table->string('burst_time')->nullable();
            $table->string('priority')->default('8');
            $table->string('parent')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('disabled')->default(false);
            $table->boolean('is_synced')->default(false)->comment('Synced with Mikrotik device');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['mikrotik_device_id', 'name']);
            $table->index('customer_id');
            $table->index('target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_queues');
    }
};

