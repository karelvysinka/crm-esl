<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_no')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date('order_date')->nullable();
            $table->string('author')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('source', 32)->default('helios');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id','order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
