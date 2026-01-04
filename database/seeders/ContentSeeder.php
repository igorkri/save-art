<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо контент вже є
        if (Content::exists()) {
            return;
        }

        // Создаем 10 тестовых записей для контента
        Content::factory()->count(10)->create();
    }
}
