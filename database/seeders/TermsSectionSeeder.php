<?php

namespace Database\Seeders;

use App\Models\TermsSection;
use Illuminate\Database\Seeder;

class TermsSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо розділи вже є
        if (TermsSection::exists()) {
            return;
        }

        TermsSection::create([
            'heading' => 'Дізнайтеся про ваші права та обов\'язки як користувача платформи.',
            'date' => '02.01.2025',
            'order' => 1,
            'is_active' => true,
        ]);

        TermsSection::create([
            'heading' => null,
            'date' => null,
            'order' => 2,
            'is_active' => true,
        ]);
    }
}
