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
        Schema::create('about', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('Заголовок');
            $table->json('feats')->comment('особливості');
            $table->json('description')->comment('Опис');
            $table->json('goals')->comment('Місія та цілі');
            $table->json('tasks')->comment('Завдання');
            $table->json('implementation')->comment('Як ми це реалізуємо');
            $table->json('results')->comment('Pезультати');
            $table->json('id_art')->comment('Благодійний фонд ID Art UA');
            $table->json('events')->comment('Події');
            $table->json('project')->comment('Проект');
            $table->json('artists')->comment('Художники');
            $table->boolean('is_active_artist')
                ->default(true)
                ->comment('Активність художників');
            $table->json('partners')->comment('Партнери');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about');
    }
};
