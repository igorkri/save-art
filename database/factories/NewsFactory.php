<?php

namespace Database\Factories;

use App\Enums\NewsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleUk = $this->faker->sentence(6);
        $titleEn = $this->faker->sentence(6);

        return [
            'title' => [
                'uk' => $titleUk,
                'en' => $titleEn,
            ],
            'slug' => Str::slug($titleEn).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'category' => $this->faker->randomElement(NewsCategory::cases()),
            'published_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'main_image' => null,
            'text_blocks' => [
                [
                    'paragraphs' => [
                        'uk' => [$this->faker->paragraph(), $this->faker->paragraph()],
                        'en' => [$this->faker->paragraph(), $this->faker->paragraph()],
                    ],
                    'image' => null,
                ],
                [
                    'paragraphs' => [
                        'uk' => [$this->faker->paragraph()],
                        'en' => [$this->faker->paragraph()],
                    ],
                    'image' => null,
                ],
            ],
            'is_active' => true,
        ];
    }

    /**
     * Новина (категорія "Новини")
     */
    public function news(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => NewsCategory::News,
        ]);
    }

    /**
     * Подія (категорія "Події")
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => NewsCategory::Event,
        ]);
    }

    /**
     * Неактивна новина
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
