<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('name_alt')->nullable();
            $table->string('eshop_url')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('product_groups')->nullOnDelete();
            $table->timestamps();

            $table->unique(['name','eshop_url']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_groups');
    }
};
