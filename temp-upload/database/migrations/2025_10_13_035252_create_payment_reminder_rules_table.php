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
        Schema::create('payment_reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama rule, misal: "Pengingat 7 Hari Sebelum"
            $table->integer('days_before_due'); // Negatif = sebelum due, 0 = due date, positif = overdue
            $table->foreignId('whatsapp_template_id')
                ->nullable()
                ->constrained('whatsapp_templates')
                ->nullOnDelete(); // Template yang digunakan
            $table->boolean('is_active')->default(true);
            $table->time('send_time')->default('09:00:00'); // Jam pengiriman
            $table->integer('priority')->default(0); // Urutan eksekusi
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'priority']);
            $table->index('days_before_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_rules');
    }
};
