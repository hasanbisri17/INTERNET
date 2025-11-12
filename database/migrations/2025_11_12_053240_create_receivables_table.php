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
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->enum('debtor_type', ['customer', 'user', 'other'])->default('other');
            $table->foreignId('debtor_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('debtor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('debtor_name')->nullable();
            $table->string('debtor_contact')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['debtor_type', 'debtor_customer_id']);
            $table->index(['debtor_type', 'debtor_user_id']);
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
