<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_availability_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('old_code', 8)->nullable();
            $table->string('new_code', 8)->nullable();
            $table->unsignedInteger('old_stock_qty')->nullable();
            $table->unsignedInteger('new_stock_qty')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->index(['product_id', 'changed_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_availability_changes');
    }
};
