<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function(Blueprint $table){
            if (Schema::hasColumn('orders','order_number')) {
                $table->string('order_number', 80)->change();
            }
            if (!Schema::hasColumn('orders','external_edit_id')) {
                $table->unsignedBigInteger('external_edit_id')->nullable()->after('order_number');
                $table->index('external_edit_id');
            }
        });
    }
    public function down(): void
    {
        Schema::table('orders', function(Blueprint $table){
            // Cannot safely shrink length or drop column without potential data loss; leave noop.
        });
    }
};
