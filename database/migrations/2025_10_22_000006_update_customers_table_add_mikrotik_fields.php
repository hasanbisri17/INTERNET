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
        Schema::table('customers', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('customers', 'mikrotik_device_id')) {
                $table->foreignId('mikrotik_device_id')->nullable()->after('internet_package_id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('customers', 'ppp_secret_id')) {
                $table->foreignId('ppp_secret_id')->nullable()->after('mikrotik_device_id')->constrained('mikrotik_ppp_secrets')->onDelete('set null');
            }
            if (!Schema::hasColumn('customers', 'mikrotik_queue_id')) {
                $table->foreignId('mikrotik_queue_id')->nullable()->after('ppp_secret_id')->constrained('mikrotik_queues')->onDelete('set null');
            }
            if (!Schema::hasColumn('customers', 'installation_date')) {
                $table->date('installation_date')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('customers', 'activation_date')) {
                $table->date('activation_date')->nullable()->after('installation_date');
            }
            if (!Schema::hasColumn('customers', 'due_date')) {
                $table->date('due_date')->nullable()->after('activation_date');
            }
            if (!Schema::hasColumn('customers', 'status')) {
                $table->enum('status', ['active', 'suspended', 'expired', 'terminated'])->default('active')->after('due_date');
            }
            if (!Schema::hasColumn('customers', 'is_isolated')) {
                $table->boolean('is_isolated')->default(false)->after('status');
            }
            if (!Schema::hasColumn('customers', 'isolated_at')) {
                $table->timestamp('isolated_at')->nullable()->after('is_isolated');
            }
            if (!Schema::hasColumn('customers', 'static_ip')) {
                $table->string('static_ip')->nullable()->after('pppoe_password');
            }
            if (!Schema::hasColumn('customers', 'mac_address')) {
                $table->string('mac_address')->nullable()->after('static_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['mikrotik_device_id']);
            $table->dropForeign(['ppp_secret_id']);
            $table->dropForeign(['mikrotik_queue_id']);
            $table->dropColumn([
                'mikrotik_device_id',
                'ppp_secret_id',
                'mikrotik_queue_id',
                'installation_date',
                'activation_date',
                'due_date',
                'status',
                'is_isolated',
                'isolated_at',
                'static_ip',
                'mac_address',
            ]);
        });
    }
};

