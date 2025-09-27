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
        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();
            $table->json('hero_title')->nullable(); // Заголовок героїчної секції
            $table->string('hero_video_poster')->nullable(); // Постер відео
            $table->string('hero_video_url')->nullable(); // URL відео

            // Секція донатів
            $table->json('donates_subtitle')->nullable(); // "ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ..."
            $table->json('donates_title')->nullable(); // "Твоя підтримка — натхнення для митця"
            $table->json('donates_text')->nullable(); // Текст опису системи донатів

            // Статистика
            $table->bigInteger('total_collected')->default(0); // Загальна сума зібраних коштів
            $table->integer('declared_projects')->default(0);
            $table->integer('active_projects')->default(0);
            $table->integer('completed_projects')->default(0);
            $table->integer('sold_projects')->default(0);

            // Партнери секція
            $table->json('partners_title')->nullable();

            // Реклама
            $table->json('ad_first_title')->nullable();
            $table->json('ad_first_button_text')->nullable();
            $table->string('ad_first_image')->nullable();
            $table->json('ad_second_title')->nullable();
            $table->json('ad_second_button_text')->nullable();
            $table->string('ad_second_image')->nullable();

            // Футер
            $table->json('footer_expert_title')->nullable();
            $table->json('footer_expert_text')->nullable();
            $table->json('footer_expert_features')->nullable(); // Масив з 3 пунктами
            $table->json('footer_expert_button_text')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_pages');
    }
};
