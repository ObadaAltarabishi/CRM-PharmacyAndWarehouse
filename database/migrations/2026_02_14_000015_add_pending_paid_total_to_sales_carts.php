<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_carts', function (Blueprint $table) {
            $table->decimal('pending_paid_total', 12, 4)->nullable()->after('pharmacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_carts', function (Blueprint $table) {
            $table->dropColumn('pending_paid_total');
        });
    }
};
