<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('ac_id')->nullable()->after('id');
            $table->timestamp('ac_updated_at')->nullable()->after('updated_at');
            $table->string('ac_hash')->nullable()->after('ac_id');
            $table->enum('marketing_status', ['subscribed','unsubscribed','bounced','none'])->default('none')->after('status');
            $table->unique('ac_id');
            $table->index('ac_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['ac_id']);
            $table->dropIndex(['ac_updated_at']);
            $table->dropColumn(['ac_id','ac_updated_at','ac_hash','marketing_status']);
        });
    }
};
