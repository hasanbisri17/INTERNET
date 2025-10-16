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
        Schema::create('broadcast_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('media_path')->nullable();
            $table->enum('media_type', ['image', 'document'])->nullable();
            $table->string('recipient_type'); // all, active, custom
            $table->json('recipient_ids')->nullable(); // For custom selection
            $table->integer('total_recipients')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        // Add broadcast_campaign_id to whats_app_messages
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->foreignId('broadcast_campaign_id')->nullable()->after('payment_id')->constrained('broadcast_campaigns')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->dropForeign(['broadcast_campaign_id']);
            $table->dropColumn('broadcast_campaign_id');
        });
        
        Schema::dropIfExists('broadcast_campaigns');
    }
};

