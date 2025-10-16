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
        Schema::table('dunning_configs', function (Blueprint $table) {
            // n8n Integration fields
            $table->boolean('n8n_enabled')->default(false)->after('notification_channels');
            $table->string('n8n_webhook_url')->nullable()->after('n8n_enabled');
            $table->string('n8n_webhook_method')->default('POST')->after('n8n_webhook_url');
            $table->text('n8n_webhook_headers')->nullable()->after('n8n_webhook_method'); // JSON headers
            $table->integer('n8n_trigger_after_days')->default(7)->after('n8n_webhook_headers');
            $table->boolean('n8n_auto_unsuspend')->default(true)->after('n8n_trigger_after_days');
            $table->text('n8n_custom_payload')->nullable()->after('n8n_auto_unsuspend'); // JSON custom payload template
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_configs', function (Blueprint $table) {
            $table->dropColumn([
                'n8n_enabled',
                'n8n_webhook_url',
                'n8n_webhook_method',
                'n8n_webhook_headers',
                'n8n_trigger_after_days',
                'n8n_auto_unsuspend',
                'n8n_custom_payload',
            ]);
        });
    }
};
