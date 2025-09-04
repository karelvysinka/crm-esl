<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop global unique index on email if exists
            try {
                $table->dropUnique('contacts_email_unique');
            } catch (\Throwable $e) {
                // ignore if not exists
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Add composite unique on (company_id, email)
            $table->unique(['company_id', 'email'], 'contacts_company_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            try {
                $table->dropUnique('contacts_company_email_unique');
            } catch (\Throwable $e) {}
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Restore global unique on email
            $table->unique('email');
        });
    }
};
