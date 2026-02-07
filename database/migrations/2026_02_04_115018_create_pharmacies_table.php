<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('pharmacy_name');
            $table->string('doctor_name');
            $table->string('doctor_phone')->unique();
            $table->string('doctor_email')->unique();
            $table->string('password');
            $table->rememberToken();
            // تاريخ التفعيل (إجباري) وبيتعبّى تلقائيًا وقت الإنشاء
            $table->timestamp('activated_at')->useCurrent();
            // علاقات
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
        Schema::dropIfExists('pharmacies');
    }
};
