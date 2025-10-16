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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->unsignedBigInteger('whatsapp_message_id')->nullable(); // Reference to whatsapp_messages (soft reference, no FK)
            $table->enum('reminder_type', ['h_minus_3', 'h_minus_1', 'h_zero', 'overdue']); // H-3, H-1, H+0, Overdue
            $table->date('reminder_date'); // Tanggal reminder seharusnya dikirim
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Index untuk query optimization
            $table->index(['payment_id', 'reminder_type']);
            $table->index(['reminder_date', 'status']);
            $table->index('status');
            $table->index('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
