<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_carts', function (Blueprint $table) {
            $table->text('pending_feedback')->nullable()->after('pending_paid_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales_carts', function (Blueprint $table) {
            $table->dropColumn('pending_feedback');
        });
    }
};
