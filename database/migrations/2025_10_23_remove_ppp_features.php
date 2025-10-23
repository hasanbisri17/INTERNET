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
        // Drop foreign keys and columns from customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'ppp_secret_id')) {
                    $table->dropForeign(['ppp_secret_id']);
                    $table->dropColumn('ppp_secret_id');
                }
                if (Schema::hasColumn('customers', 'pppoe_username')) {
                    $table->dropColumn('pppoe_username');
                }
                if (Schema::hasColumn('customers', 'pppoe_password')) {
                    $table->dropColumn('pppoe_password');
                }
            });
        }

        // Drop foreign keys and columns from internet_packages table
        if (Schema::hasTable('internet_packages')) {
            Schema::table('internet_packages', function (Blueprint $table) {
                if (Schema::hasColumn('internet_packages', 'mikrotik_profile_id')) {
                    $table->dropForeign(['mikrotik_profile_id']);
                    $table->dropColumn('mikrotik_profile_id');
                }
            });
        }

        // Drop foreign keys and columns from auto_isolir_configs table
        if (Schema::hasTable('auto_isolir_configs')) {
            Schema::table('auto_isolir_configs', function (Blueprint $table) {
                if (Schema::hasColumn('auto_isolir_configs', 'isolir_profile_id')) {
                    $table->dropForeign(['isolir_profile_id']);
                    $table->dropColumn('isolir_profile_id');
                }
                if (Schema::hasColumn('auto_isolir_configs', 'isolir_profile_name')) {
                    $table->dropColumn('isolir_profile_name');
                }
            });
        }

        // Drop mikrotik_ppp_secrets table
        Schema::dropIfExists('mikrotik_ppp_secrets');

        // Drop mikrotik_profiles table
        Schema::dropIfExists('mikrotik_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate mikrotik_profiles table
        Schema::create('mikrotik_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained('mikrotik_devices')->cascadeOnDelete();
            $table->string('profile_name');
            $table->string('download_speed')->nullable();
            $table->string('upload_speed')->nullable();
            $table->string('burst_download')->nullable();
            $table->string('burst_upload')->nullable();
            $table->string('burst_threshold_download')->nullable();
            $table->string('burst_threshold_upload')->nullable();
            $table->integer('burst_time')->nullable();
            $table->string('pool_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate mikrotik_ppp_secrets table
        Schema::create('mikrotik_ppp_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained('mikrotik_devices')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('password');
            $table->string('service')->default('pppoe');
            $table->string('profile')->nullable();
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('disabled')->default(false);
            $table->timestamps();
        });

        // Add columns back to customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('pppoe_username')->nullable()->after('connection_type');
                $table->string('pppoe_password')->nullable()->after('pppoe_username');
                $table->foreignId('ppp_secret_id')->nullable()->after('mikrotik_device_id')->constrained('mikrotik_ppp_secrets')->nullOnDelete();
            });
        }

        // Add columns back to internet_packages table
        if (Schema::hasTable('internet_packages')) {
            Schema::table('internet_packages', function (Blueprint $table) {
                $table->foreignId('mikrotik_profile_id')->nullable()->after('is_active')->constrained('mikrotik_profiles')->nullOnDelete();
            });
        }

        // Add columns back to auto_isolir_configs table
        if (Schema::hasTable('auto_isolir_configs')) {
            Schema::table('auto_isolir_configs', function (Blueprint $table) {
                $table->foreignId('isolir_profile_id')->nullable()->after('mikrotik_device_id')->constrained('mikrotik_profiles')->nullOnDelete();
                $table->string('isolir_profile_name')->nullable()->after('warning_days');
            });
        }
    }
};

