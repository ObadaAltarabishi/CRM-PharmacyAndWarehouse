<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')
                ->constrained('pharmacies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('warehouse_id')
                ->constrained('warehouse')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['pharmacy_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_ratings');
    }
};
