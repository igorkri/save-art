<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FaqCategory>
 */
class FaqCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameUk = $this->faker->words(3, true);

        return [
            'name' => $nameUk,
            'slug' => Str::slug($nameUk),
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
