<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо категорії вже є
        if (FaqCategory::exists()) {
            return;
        }

        $categories = [
            [
                'name' => 'Загальні питання',
                'slug' => 'general',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Донати та оплата',
                'slug' => 'donations',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Для митців',
                'slug' => 'artists',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Для меценатів',
                'slug' => 'patrons',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Технічна підтримка',
                'slug' => 'support',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            FaqCategory::create($category);
        }
    }
}
