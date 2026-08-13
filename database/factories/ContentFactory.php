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
        $pageTitle = $this->faker->sentence();

        return [
            'page_title' => $pageTitle,
            'title' => $this->faker->sentence(),
            'slug' => Str::slug($pageTitle),
            'content' => $this->faker->paragraphs(3, true),
            'meta_title' => $this->faker->sentence(),
            'meta_description' => $this->faker->sentence(),
            'meta_keywords' => implode(', ', $this->faker->words(5)),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
