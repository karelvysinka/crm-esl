<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_sync_runs', function(Blueprint $table){
            $table->id();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status')->default('running'); // running|success|failed
            $table->string('message',500)->nullable();
            $table->unsignedInteger('new_orders')->default(0);
            $table->unsignedInteger('updated_orders')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_sync_runs'); }
};
