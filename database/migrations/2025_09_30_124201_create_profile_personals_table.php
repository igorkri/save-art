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
        Schema::create('profile_personals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача');
            // аватар
            $table->string('avatar')->nullable()->comment('Аватар користувача');
            // ім'я та прізвище
            $table->json('full_name')->comment('Ім\'я та прізвище користувача');
            // професія
            $table->json('profession')->nullable()->comment('Професія користувача');
            // теги
            $table->json('tags')->nullable()->comment('Теги користувача');
            // країна
            $table->json('country')->nullable()->comment('Країна користувача');
            // область
            $table->json('region')->nullable()->comment('Область користувача');
            // місто
            $table->json('city')->nullable()->comment('Місто користувача');
            // поштовий індекс
            $table->string('postal_code')->nullable()->comment('Поштовий індекс користувача');
            // перевага ролі (меценат або творець)
            $table->string('role')->nullable()->comment('Перевага ролі користувача (меценат або творець)');
            //опис (біо)
            $table->json('description')->nullable()->comment('Опис (біо) користувача');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_personals');
    }
};
