<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // Additional helpful indexes
            $table->index('contact_id', 'sales_orders_contact_idx');
            $table->index('order_date', 'sales_orders_order_date_idx');
            $table->index(['company_id', 'contact_id'], 'sales_orders_company_contact_idx');
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->index('sku', 'so_items_sku_idx');
            $table->index('product_group', 'so_items_group_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['last_name', 'first_name'], 'contacts_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('sales_orders_contact_idx');
            $table->dropIndex('sales_orders_order_date_idx');
            $table->dropIndex('sales_orders_company_contact_idx');
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex('so_items_sku_idx');
            $table->dropIndex('so_items_group_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_name_idx');
        });
    }
};
