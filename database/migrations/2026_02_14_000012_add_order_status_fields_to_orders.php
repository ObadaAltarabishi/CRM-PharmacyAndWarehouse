<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('rejected_at');
            $table->timestamp('issue_at')->nullable()->after('received_at');
            $table->text('issue_note')->nullable()->after('issue_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'issue_at', 'issue_note']);
        });
    }
};
