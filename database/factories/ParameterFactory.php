<?php

namespace Database\Factories;

use App\Enums\ParameterType;
use App\Models\ArtCategory;
use App\Models\Parameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parameter>
 */
class ParameterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'art_category_id' => ArtCategory::factory(),
            'name' => ['uk' => $this->faker->words(2, true), 'en' => $this->faker->words(2, true)],
            'type' => ParameterType::List,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Параметр з довільним значенням (без списку варіантів)
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ParameterType::Custom,
        ]);
    }
}
