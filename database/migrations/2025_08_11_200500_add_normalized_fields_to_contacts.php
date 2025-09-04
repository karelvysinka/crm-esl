<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('normalized_email')->nullable()->after('email');
            $table->string('normalized_phone')->nullable()->after('phone');
            $table->index('normalized_email');
            $table->index('normalized_phone');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['normalized_email']);
            $table->dropIndex(['normalized_phone']);
            $table->dropColumn(['normalized_email','normalized_phone']);
        });
    }
};
