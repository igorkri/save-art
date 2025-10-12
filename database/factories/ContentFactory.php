<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content>
 */
class ContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pageTitleUk = $this->faker->sentence();
        $pageTitleEn = $this->faker->sentence();
        $titleUk = $this->faker->sentence();
        $titleEn = $this->faker->sentence();

        return [
            'page_title' => ['uk' => $pageTitleUk, 'en' => $pageTitleEn],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'slug' => Str::slug($pageTitleEn),
            'content' => [
                'uk' => $this->faker->paragraphs(3, true),
                'en' => $this->faker->paragraphs(3, true),
            ],
            'meta_title' => ['uk' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
            'meta_description' => ['uk' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
            'meta_keywords' => ['uk' => $this->faker->words(5), 'en' => $this->faker->words(5)],
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
