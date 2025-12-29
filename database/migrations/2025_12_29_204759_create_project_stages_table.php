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
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();

            // Зв'язок з проєктом
            $table->foreignId('project_id')->constrained()->onDelete('cascade')->comment('ID проєкту');

            // Порядок та статус
            $table->unsignedInteger('order')->default(0)->comment('Порядок етапу');
            $table->string('status')->default('planned')->comment('Статус етапу: planned, in_progress, completed');

            // Інформація про етап (мультимовні поля)
            $table->json('title')->comment('Назва етапу (uk, en)');
            $table->json('description')->nullable()->comment('Опис етапу (uk, en)');

            // Бюджет етапу
            $table->decimal('budget_planned', 15, 2)->default(0)->comment('Запланована сума');
            $table->decimal('budget_actual', 15, 2)->nullable()->comment('Фактична сума');

            // Терміни
            $table->unsignedInteger('days_planned')->nullable()->comment('Заплановано днів');
            $table->date('started_at')->nullable()->comment('Дата початку етапу');
            $table->date('completed_at')->nullable()->comment('Дата завершення етапу');

            // Документальне підтвердження (JSON масив: [{type, file, description}])
            $table->json('documents')->nullable()->comment('Документи підтвердження (чеки, фотографії)');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_stages');
    }
};
