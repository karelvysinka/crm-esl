<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_sync_settings', function(Blueprint $table){
            $table->id();
            $table->string('source_url');
            $table->string('username')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->unsignedInteger('interval_minutes')->default(15);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_sync_settings'); }
};
