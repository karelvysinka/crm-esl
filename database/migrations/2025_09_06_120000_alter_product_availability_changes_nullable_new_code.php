<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('product_availability_changes', function(Blueprint $table){
            $table->string('new_code',8)->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('product_availability_changes', function(Blueprint $table){
            $table->string('new_code',8)->nullable(false)->change();
        });
    }
};
