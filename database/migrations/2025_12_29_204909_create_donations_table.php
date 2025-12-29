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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // Зв'язки
            $table->foreignId('project_id')->constrained()->onDelete('cascade')->comment('ID проєкту');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('ID донатера (null = анонімний)');
            $table->foreignId('project_bonus_id')->nullable()->constrained()->onDelete('set null')->comment('ID обраного бонусу');

            // Інформація про донат
            $table->decimal('amount', 15, 2)->comment('Сума донату');
            $table->string('currency')->default('UAH')->comment('Валюта донату (UAH, USD, EUR)');
            $table->decimal('amount_usd', 15, 2)->nullable()->comment('Сума в USD для статистики');

            // Тип донатера
            $table->string('donor_type')->default('personal')->comment('Тип донатера: personal або legal');
            $table->boolean('is_anonymous')->default(false)->comment('Чи анонімний донат');

            // Дані для анонімного/неавторизованого донатера
            $table->string('donor_name')->nullable()->comment('Ім\'я донатера (якщо анонімний)');
            $table->string('donor_email')->nullable()->comment('Email донатера');

            // Статус оплати
            $table->string('status')->default('pending')->comment('Статус: pending, paid, failed, refunded');
            $table->string('payment_method')->nullable()->comment('Метод оплати');
            $table->string('payment_id')->nullable()->comment('ID транзакції платіжної системи');

            $table->timestamp('paid_at')->nullable()->comment('Дата оплати');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
