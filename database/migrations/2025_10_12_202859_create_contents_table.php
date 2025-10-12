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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->json('page_title')->nullable()->comment('Назва сторінки');
            $table->json('title')->nullable()->comment('Заголовок');
            $table->string('slug')->index()->unique()->comment('Унікальний ідентифікатор');
            $table->json('content')->nullable()->comment('Контент');
            $table->json('meta_title')->nullable()->comment('Мета заголовок');
            $table->json('meta_description')->nullable()->comment('Мета опис');
            $table->json('meta_keywords')->nullable()->comment('Мета ключові слова');
            $table->boolean('is_active')->default(true)->comment('Статус активності');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
