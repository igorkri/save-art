<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'slug' => Str::slug($name).'-'.Str::random(6),
            'name' => ['uk' => $name, 'en' => $name],
            'website' => fake()->url(),
            'country' => ['uk' => 'Україна', 'en' => 'Ukraine'],
            'city' => ['uk' => fake()->city(), 'en' => fake()->city()],
            'description' => ['uk' => fake()->paragraphs(2), 'en' => fake()->paragraphs(2)],
            'social_links' => [],
        ];
    }
}
