<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number', 32)->unique();
            $table->dateTime('order_created_at')->index(); // origin timestamp from eshop (local timezone)
            $table->unsignedBigInteger('total_vat_cents')->default(0);
            $table->char('currency', 3)->default('CZK');
            $table->dateTime('fetched_at')->nullable()->comment('When the order was first fetched into CRM');
            $table->char('source_raw_hash', 40)->nullable()->comment('Hash of scraped HTML blocks for change detection');
            $table->boolean('is_completed')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
