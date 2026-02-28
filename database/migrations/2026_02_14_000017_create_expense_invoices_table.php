<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')
                ->nullable()
                ->constrained('pharmacies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouse')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('amount', 12, 4);
            $table->string('created_by_name');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_invoices');
    }
};
