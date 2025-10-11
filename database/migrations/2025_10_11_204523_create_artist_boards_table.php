<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artist_boards', function (Blueprint $table) {
            $table->id();
            $table->json('titles')->nullable()->comment('Назви');
            $table->json('logo_museums')->nullable()->comment('Логотипи');
            $table->json('descriptions')->nullable()->comment('Опис');
            $table->json('data')->nullable()->comment('Дані авторів та їхні роботи');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_boards');
    }
};
