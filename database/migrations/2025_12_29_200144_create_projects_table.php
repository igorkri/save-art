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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Автор проєкту
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача, який створив проєкт');
            $table->string('user_type')->default('personal')->comment('Тип автора: personal (фіз. особа) або legal (юр. особа)');

            // Ідентифікація
            $table->string('code')->unique()->comment('Унікальний код проєкту');
            $table->string('slug')->unique()->comment('URL-адреса проєкту');

            // Статуси
            $table->string('status')->default('draft')->comment('Статус проєкту: draft, moderation, announced, in_progress, paused, completed, sold, rejected');
            $table->string('status_moderation')->default('pending')->comment('Статус модерації: pending, approved, rejected');

            // Основна інформація (мультимовні поля)
            $table->json('title')->comment('Назва проєкту (uk, en)');
            $table->json('short_description')->nullable()->comment('Короткий опис (до 500 символів) (uk, en)');
            $table->string('cover')->nullable()->comment('Обкладинка проєкту');
            $table->json('tags')->nullable()->comment('Теги проєкту');

            // Галузь мистецтва
            $table->string('art_category')->nullable()->comment('Галузь мистецтва (сценічне, візуальне, образотворче, література, музичне, інше)');
            $table->string('art_subcategory')->nullable()->comment('Підкатегорія мистецтва');

            // Бюджет
            $table->string('currency')->default('UAH')->comment('Валюта проєкту (UAH, USD, EUR)');
            $table->decimal('budget_goal', 15, 2)->default(0)->comment('Необхідна сума');
            $table->decimal('budget_collected', 15, 2)->default(0)->comment('Зібрана сума');
            $table->integer('estimated_days')->nullable()->comment('Орієнтовна кількість днів на створення');

            // Характеристики проєкту (JSON масив: [{name, description}])
            $table->json('characteristics')->nullable()->comment('Характеристики проєкту');

            // Бюджет деталі (JSON масив: [{name, cost}])
            $table->json('budget_items')->nullable()->comment('Деталі бюджету');

            // Додаткова інформація (JSON масив блоків контенту)
            $table->json('additional_info')->nullable()->comment('Додаткова інформація про проєкт');

            // Результат завершеного проєкту
            $table->json('final_result')->nullable()->comment('Фінальний результат (зображення, галерея, відео)');

            // Кількість лайків та донатів
            $table->unsignedInteger('likes_count')->default(0)->comment('Кількість лайків');
            $table->unsignedInteger('donors_count')->default(0)->comment('Кількість донатерів');

            // Дати
            $table->timestamp('announced_at')->nullable()->comment('Дата оголошення проєкту');
            $table->timestamp('planned_completion_at')->nullable()->comment('Запланована дата завершення');
            $table->timestamp('completed_at')->nullable()->comment('Фактична дата завершення');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
