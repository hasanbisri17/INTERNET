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
        Schema::create('mikrotik_ip_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('binding_id')->nullable()->comment('Binding ID from MikroTik (.id)');
            $table->string('mac_address')->nullable()->comment('MAC Address');
            $table->string('address')->nullable()->comment('IP Address');
            $table->string('to_address')->nullable()->comment('To Address');
            $table->string('server')->default('all')->comment('Hotspot server name');
            $table->enum('type', ['regular', 'bypassed', 'blocked'])->default('regular')->comment('Binding type');
            $table->string('comment')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->boolean('is_synced')->default(false)->comment('Synced with MikroTik device');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('mikrotik_device_id');
            $table->index('mac_address');
            $table->index('address');
            $table->index('type');
            $table->unique(['mikrotik_device_id', 'binding_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_ip_bindings');
    }
};

