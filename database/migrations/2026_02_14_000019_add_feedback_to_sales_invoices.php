<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoices', 'feedback')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->text('feedback')->nullable()->after('discount_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'feedback')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropColumn('feedback');
            });
        }
    }
};
