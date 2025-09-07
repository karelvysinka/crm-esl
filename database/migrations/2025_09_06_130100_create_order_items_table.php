<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('external_item_id', 32)->nullable()->index();
            $table->string('name');
            $table->string('product_code', 64)->nullable()->index();
            $table->string('variant_code', 64)->nullable()->index();
            $table->string('specification', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 16)->nullable();
            $table->unsignedInteger('unit_price_vat_cents')->default(0);
            $table->unsignedTinyInteger('vat_rate_percent')->default(21);
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->unsignedInteger('total_ex_vat_cents')->nullable();
            $table->unsignedInteger('total_vat_cents')->nullable();
            $table->enum('line_type', ['product','shipping','payment','other'])->default('product')->index();
            $table->char('currency', 3)->default('CZK');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
