<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_invoices', function (Blueprint $table) {
            $table->string('created_by_name')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('expense_invoices', function (Blueprint $table) {
            $table->dropColumn('created_by_name');
        });
    }
};
