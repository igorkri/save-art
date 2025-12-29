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
        Schema::create('project_likes', function (Blueprint $table) {
            $table->id();

            // Зв'язки
            $table->foreignId('project_id')->constrained()->onDelete('cascade')->comment('ID проєкту');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача');

            $table->timestamps();

            // Унікальний індекс - один лайк від користувача на проєкт
            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_likes');
    }
};
