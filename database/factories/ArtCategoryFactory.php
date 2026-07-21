<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArtCategory>
 */
class ArtCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'slug' => $this->faker->unique()->slug(2),
            'name' => [
                'uk' => $this->faker->words(2, true),
                'en' => $this->faker->words(2, true),
            ],
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
