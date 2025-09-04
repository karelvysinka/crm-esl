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
        if (DB::getDriverName() === 'sqlite') {
            // Skip enum alteration for sqlite test runs
            return;
        }
        DB::statement("ALTER TABLE contacts MODIFY COLUMN status ENUM('active', 'inactive', 'blocked', 'lead', 'prospect') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE contacts MODIFY COLUMN status ENUM('active', 'inactive', 'blocked') DEFAULT 'active'");
    }
};
