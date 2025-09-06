<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_price_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('old_price_cents');
            $table->unsignedInteger('new_price_cents');
            $table->timestamp('changed_at')->useCurrent();
            $table->index(['product_id', 'changed_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_price_changes');
    }
};
