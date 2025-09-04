<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('alt_code')->nullable();
            $table->string('name');
            $table->string('name_alt')->nullable();
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('unit_price_disc', 15, 4)->nullable();
            $table->decimal('cost', 15, 4)->nullable();
            $table->decimal('cost_disc', 15, 4)->nullable();
            $table->decimal('discounts_card', 10, 2)->nullable();
            $table->decimal('discounts_group', 10, 2)->nullable();
            $table->string('product_group')->nullable();
            $table->string('eshop_category_url')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('currency', 3)->default('CZK');
            $table->timestamps();
            $table->index('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
