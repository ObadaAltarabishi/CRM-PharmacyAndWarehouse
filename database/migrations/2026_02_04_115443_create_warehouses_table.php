<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse', function (Blueprint $table) {
            $table->id();

            // معلومات المستودع (عدّلها حسب مشروعك)
            $table->string('warehouse_name');
            $table->string('owner_name');
            $table->string('owner_phone')->unique();
            $table->string('owner_email')->unique();
            $table->string('password');
            // تاريخ التفعيل (يتعبّى تلقائي)
            $table->timestamp('activated_at')->useCurrent();
            // العلاقات
            $table->foreignId('region_id')
            ->references('id')
            ->on('regions')
            ->cascadeOnUpdate();
            
            $table->foreignId('admin_id')
            ->references('id')
            ->on('admins')
            ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse');
    }
};
