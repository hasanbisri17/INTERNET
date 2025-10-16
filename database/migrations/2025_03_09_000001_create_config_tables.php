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
        // Tabel untuk konfigurasi Dunning Engine
        Schema::create('dunning_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('grace_period_days')->default(0);
            $table->integer('reminder_days_before')->default(3);
            $table->boolean('auto_suspend')->default(false);
            $table->integer('suspend_after_days')->default(7);
            $table->boolean('auto_unsuspend_on_payment')->default(true);
            $table->json('notification_channels')->nullable();
            $table->timestamps();
        });

        // Tabel untuk konfigurasi AAA
        Schema::create('aaa_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_url');
            $table->string('api_username');
            $table->string('api_password');
            $table->string('api_key')->nullable();
            $table->string('connection_type')->default('radius');
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(30);
            $table->string('captive_portal_url')->nullable();
            $table->boolean('enable_captive_portal')->default(false);
            $table->string('captive_portal_template')->default('default');
            $table->timestamps();
        });

        // Tabel untuk konfigurasi Portal Pelanggan
        Schema::create('customer_portal_configs', function (Blueprint $table) {
            $table->id();
            $table->string('portal_name')->default('Portal Pelanggan');
            $table->string('portal_logo')->nullable();
            $table->string('portal_theme')->default('default');
            $table->boolean('enable_registration')->default(true);
            $table->boolean('require_email_verification')->default(true);
            $table->boolean('enable_password_reset')->default(true);
            $table->boolean('enable_payment_feature')->default(true);
            $table->boolean('enable_ticket_feature')->default(false);
            $table->boolean('enable_usage_stats')->default(true);
            $table->json('visible_menu_items')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dunning_configs');
        Schema::dropIfExists('aaa_configs');
        Schema::dropIfExists('customer_portal_configs');
    }
};