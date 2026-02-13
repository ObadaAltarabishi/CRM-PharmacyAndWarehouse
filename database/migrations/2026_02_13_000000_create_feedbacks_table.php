<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('pharmacy_id')
                ->nullable()
                ->constrained('pharmacies')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouse')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
