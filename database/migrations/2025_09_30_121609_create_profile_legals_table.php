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
        Schema::create('profile_legals', function (Blueprint $table) {
            $table->id();
            // зв'язок з користувачем
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача');
            // валюта за замовчуванням
            $table->string('currency', 3)->default('UAH')->comment('ISO 4217 currency code');
            // признак приватної особи або юридичної
            $table->boolean('is_legal')->default(false)->comment('Признак приватної особи або юридичної');
            // логотип компанії
            $table->string('logo')->nullable()->comment('Логотип компанії');
            // назва компанії або ПІБ приватної особи
            $table->json('name')->comment('Назва компанії або ПІБ приватної особи');
            // ЕДРПОУ
            $table->string('edrpou')->nullable()->comment('ЄДРПОУ');
            // уповноважена особа
            $table->json('authorized_person')->nullable()->comment('Уповноважена особа');
            // адреса
            $table->json('address')->nullable()->comment('Адреса');
            // телефон
            $table->string('phone')->nullable()->comment('Телефон');
            // email
            $table->string('email')->nullable()->comment('Email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_legals');
    }
};
