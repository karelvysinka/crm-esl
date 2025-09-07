<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders','external_edit_id')) {
                $table->unsignedBigInteger('external_edit_id')->nullable()->after('id')->index();
            }
        });
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders','external_edit_id')) {
                $table->dropColumn('external_edit_id');
            }
        });
    }
};
