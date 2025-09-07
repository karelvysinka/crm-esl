<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_state_changes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('old_code', 32)->nullable();
            $table->string('new_code', 32); // raw state code or mapped label key
            $table->dateTime('changed_at'); // timestamp from eshop if derivable, else = detected_at
            $table->dateTime('detected_at'); // when we noticed the change
            $table->char('source_snapshot_hash', 40);
            $table->timestamps();
            $table->index(['order_id','changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_state_changes');
    }
};
