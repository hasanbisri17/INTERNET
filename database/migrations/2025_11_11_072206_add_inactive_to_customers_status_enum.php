<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'inactive' to the status enum
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'expired', 'terminated') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'inactive' from the status enum
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'suspended', 'expired', 'terminated') NOT NULL DEFAULT 'active'");
    }
};
