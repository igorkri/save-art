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
                'name' => ['uk' => 'Загальні питання', 'en' => 'General Questions'],
                'slug' => 'general',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => ['uk' => 'Донати та оплата', 'en' => 'Donations and Payment'],
                'slug' => 'donations',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => ['uk' => 'Для митців', 'en' => 'For Artists'],
                'slug' => 'artists',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => ['uk' => 'Для меценатів', 'en' => 'For Patrons'],
                'slug' => 'patrons',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => ['uk' => 'Технічна підтримка', 'en' => 'Technical Support'],
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
