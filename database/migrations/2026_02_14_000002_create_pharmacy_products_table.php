<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')
                ->constrained('pharmacies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('cost_price', 12, 4);
            $table->decimal('default_sell_price', 12, 4);
            $table->timestamps();

            $table->unique(['pharmacy_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_products');
    }
};
