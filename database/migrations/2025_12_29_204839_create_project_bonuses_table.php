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
        Schema::create('project_bonuses', function (Blueprint $table) {
            $table->id();

            // Зв'язок з проєктом
            $table->foreignId('project_id')->constrained()->onDelete('cascade')->comment('ID проєкту');

            // Інформація про бонус (мультимовні поля)
            $table->json('title')->comment('Назва бонусу (uk, en)');
            $table->json('description')->nullable()->comment('Опис бонусу (uk, en)');

            // Умови отримання
            $table->decimal('min_donation', 15, 2)->default(0)->comment('Мінімальна сума донату для отримання бонусу');
            $table->unsignedInteger('quantity')->nullable()->comment('Кількість доступних бонусів (null = необмежено)');
            $table->unsignedInteger('quantity_claimed')->default(0)->comment('Кількість виданих бонусів');

            // Порядок відображення
            $table->unsignedInteger('order')->default(0)->comment('Порядок відображення');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_bonuses');
    }
};
