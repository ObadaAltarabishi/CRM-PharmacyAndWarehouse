<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->string('authenticatable_type');
            $table->unsignedBigInteger('authenticatable_id');
            $table->string('email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('resend_count')->default(0);
            $table->dateTime('last_sent_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->index(['authenticatable_type', 'authenticatable_id']);
            $table->index(['email', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
