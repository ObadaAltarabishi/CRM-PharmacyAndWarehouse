<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index(['warehouse_id', 'status']);
            $table->index(['pharmacy_id', 'created_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index(['pharmacy_id', 'created_at']);
        });

        Schema::table('expense_invoices', function (Blueprint $table) {
            $table->index(['pharmacy_id', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
        });

        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->index(['warehouse_id', 'product_id']);
        });

        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->index(['pharmacy_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['warehouse_id', 'status']);
            $table->dropIndex(['pharmacy_id', 'created_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['pharmacy_id', 'created_at']);
        });

        Schema::table('expense_invoices', function (Blueprint $table) {
            $table->dropIndex(['pharmacy_id', 'created_at']);
            $table->dropIndex(['warehouse_id', 'created_at']);
        });

        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'product_id']);
        });

        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropIndex(['pharmacy_id', 'product_id']);
        });
    }
};
