<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_cart_id')
                ->constrained('order_carts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['order_cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cart_items');
    }
};
