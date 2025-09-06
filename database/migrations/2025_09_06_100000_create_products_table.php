<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 32)->unique();
            $table->string('group_id', 32)->nullable()->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('price_vat_cents');
            $table->char('currency', 3)->default('CZK');
            $table->string('manufacturer', 80)->nullable();
            $table->string('ean', 20)->nullable()->index();
            $table->string('category_path', 255);
            $table->char('category_hash', 40)->index();
            $table->string('url', 255);
            $table->string('image_url', 255)->nullable();
            $table->string('availability_code', 8)->nullable()->index();
            $table->string('availability_text', 32)->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->timestamp('availability_synced_at')->nullable();
            $table->char('hash_payload', 40);
            $table->timestamp('first_imported_at')->useCurrent();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_price_changed_at')->nullable();
            $table->timestamp('last_availability_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
