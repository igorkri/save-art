<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('art_category_id')->constrained()->cascadeOnDelete()->comment('Категорія мистецтва, до якої належить характеристика');
            $table->json('name')->comment('Назва характеристики (uk, en)');
            $table->string('type')->default('list')->comment('Тип: list (вибір зі значень) або custom (довільне значення)');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
