<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function(Blueprint $table){
            $table->string('last_state_code', 32)->nullable()->index()->after('is_completed');
        });
    }
    public function down(): void
    {
        Schema::table('orders', function(Blueprint $table){
            $table->dropColumn('last_state_code');
        });
    }
};
