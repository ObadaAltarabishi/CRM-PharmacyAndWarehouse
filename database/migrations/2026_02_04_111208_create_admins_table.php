<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->rememberToken();
            // role: admin أو super_admin
            $table->string('role')->default('admin');
            $table->foreignId('region_id')
            ->references('id')
            ->on('regions')
            ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
