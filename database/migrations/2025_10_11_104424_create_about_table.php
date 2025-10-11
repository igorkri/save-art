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
            $table->json('title')->nullable()->comment('Заголовок');
            $table->json('feats')->nullable()->comment('особливості');
            $table->json('description')->nullable()->comment('Опис');
            $table->json('goals')->nullable()->comment('Місія та цілі');
            $table->json('tasks')->nullable()->comment('Завдання');
            $table->json('implementation')->nullable()->comment('Як ми це реалізуємо');
            $table->json('results')->nullable()->comment('Pезультати');
            $table->json('id_art')->nullable()->comment('Благодійний фонд ID Art UA');
            $table->json('events')->nullable()->comment('Події');
            $table->json('project')->nullable()->comment('Проект');
            $table->json('artists')->nullable()->comment('Художники');
            $table->boolean('is_active_artist')
                ->nullable()
                ->default(true)
                ->comment('Активність художників');
            $table->json('partners')->nullable()->comment('Партнери');
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
